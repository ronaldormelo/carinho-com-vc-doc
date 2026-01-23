<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servico de controle de horario comercial.
 *
 * Gerencia verificacao de dias uteis, feriados e
 * horarios de atendimento para automacoes.
 */
class WorkingHoursService
{
    private string $timezone;
    private string $startTime;
    private string $endTime;
    private bool $workOnSaturday;
    private string $saturdayEnd;

    public function __construct()
    {
        $this->timezone = config('atendimento.timezone', 'America/Sao_Paulo');
        $this->startTime = config('atendimento.working_hours.start', '08:00');
        $this->endTime = config('atendimento.working_hours.end', '18:00');
        $this->workOnSaturday = config('atendimento.working_hours.saturday', false);
        $this->saturdayEnd = config('atendimento.working_hours.saturday_end', '12:00');
    }

    /**
     * Verifica se o momento atual esta dentro do horario comercial.
     */
    public function isWithinWorkingHours(?Carbon $dateTime = null): bool
    {
        $now = $dateTime ?? Carbon::now($this->timezone);

        // Verifica se e dia util
        if (!$this->isBusinessDay($now)) {
            return false;
        }

        // Define horario de fim baseado no dia
        $endTime = $this->getEndTimeForDay($now);

        $startAt = Carbon::parse($now->toDateString() . ' ' . $this->startTime, $this->timezone);
        $endAt = Carbon::parse($now->toDateString() . ' ' . $endTime, $this->timezone);

        return $now->between($startAt, $endAt);
    }

    /**
     * Verifica se esta fora do horario comercial.
     */
    public function isOutsideWorkingHours(?Carbon $dateTime = null): bool
    {
        return !$this->isWithinWorkingHours($dateTime);
    }

    /**
     * Verifica se a data e um dia util (nao e fim de semana nem feriado).
     */
    public function isBusinessDay(?Carbon $date = null): bool
    {
        $checkDate = $date ?? Carbon::now($this->timezone);

        // Domingo nunca e dia util
        if ($checkDate->isSunday()) {
            return false;
        }

        // Sabado so e dia util se configurado
        if ($checkDate->isSaturday() && !$this->workOnSaturday) {
            return false;
        }

        // Verifica feriados
        if ($this->isHoliday($checkDate)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se a data e um feriado.
     */
    public function isHoliday(Carbon $date): bool
    {
        $holidays = $this->getHolidaysForYear($date->year);
        $dateStr = $date->format('Y-m-d');
        $monthDay = $date->format('m-d');

        foreach ($holidays as $holiday) {
            // Verifica data exata
            if ($holiday->date === $dateStr) {
                return true;
            }

            // Verifica feriados recorrentes (mesmo mes/dia)
            if ($holiday->year_recurring && substr($holiday->date, 5) === $monthDay) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtem o proximo horario comercial disponivel.
     */
    public function getNextBusinessTime(?Carbon $fromTime = null): Carbon
    {
        $current = $fromTime ?? Carbon::now($this->timezone);

        // Se ja esta em horario comercial, retorna o momento atual
        if ($this->isWithinWorkingHours($current)) {
            return $current;
        }

        // Avanca para o proximo dia util
        $nextDay = $current->copy();

        // Se passou do horario de fim hoje, vai para amanha
        $endTime = $this->getEndTimeForDay($current);
        $endAt = Carbon::parse($current->toDateString() . ' ' . $endTime, $this->timezone);

        if ($current->gt($endAt) || !$this->isBusinessDay($current)) {
            $nextDay->addDay()->startOfDay();
        }

        // Encontra o proximo dia util
        $maxDays = 10; // Limite de seguranca
        $attempts = 0;

        while (!$this->isBusinessDay($nextDay) && $attempts < $maxDays) {
            $nextDay->addDay();
            $attempts++;
        }

        // Retorna o inicio do horario comercial do dia encontrado
        return Carbon::parse($nextDay->toDateString() . ' ' . $this->startTime, $this->timezone);
    }

    /**
     * Calcula tempo em minutos ate o horario comercial.
     */
    public function getMinutesUntilOpen(?Carbon $fromTime = null): int
    {
        $current = $fromTime ?? Carbon::now($this->timezone);

        if ($this->isWithinWorkingHours($current)) {
            return 0;
        }

        $nextOpen = $this->getNextBusinessTime($current);

        return (int) $current->diffInMinutes($nextOpen);
    }

    /**
     * Obtem mensagem padrao para fora do horario.
     */
    public function getAfterHoursMessage(): string
    {
        $nextOpen = $this->getNextBusinessTime();

        $dayName = $this->getDayNameInPortuguese($nextOpen);
        $time = $nextOpen->format('H:i');

        return "Nosso atendimento funciona de segunda a " .
            ($this->workOnSaturday ? "sabado" : "sexta") .
            ", das {$this->startTime} as {$this->endTime}. " .
            "Retornaremos na {$dayName} as {$time}.";
    }

    /**
     * Obtem lista de feriados do ano.
     */
    public function getHolidaysForYear(int $year): array
    {
        $cacheKey = "holidays:{$year}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($year) {
            return DB::table('holidays')
                ->where(function ($query) use ($year) {
                    $query->whereYear('date', $year)
                        ->orWhere('year_recurring', 1);
                })
                ->orderBy('date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Adiciona um feriado.
     */
    public function addHoliday(string $date, string $description, bool $recurring = false): int
    {
        $id = DB::table('holidays')->insertGetId([
            'date' => $date,
            'description' => $description,
            'year_recurring' => $recurring ? 1 : 0,
        ]);

        // Limpa cache
        $year = Carbon::parse($date)->year;
        Cache::forget("holidays:{$year}");

        return $id;
    }

    /**
     * Remove um feriado.
     */
    public function removeHoliday(int $holidayId): bool
    {
        $holiday = DB::table('holidays')->where('id', $holidayId)->first();

        if (!$holiday) {
            return false;
        }

        DB::table('holidays')->where('id', $holidayId)->delete();

        // Limpa cache
        $year = Carbon::parse($holiday->date)->year;
        Cache::forget("holidays:{$year}");

        return true;
    }

    /**
     * Obtem configuracao atual de horarios.
     */
    public function getConfiguration(): array
    {
        return [
            'timezone' => $this->timezone,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'work_on_saturday' => $this->workOnSaturday,
            'saturday_end_time' => $this->saturdayEnd,
            'current_status' => $this->isWithinWorkingHours() ? 'open' : 'closed',
            'next_open' => $this->getNextBusinessTime()->toDateTimeString(),
        ];
    }

    private function getEndTimeForDay(Carbon $date): string
    {
        if ($date->isSaturday() && $this->workOnSaturday) {
            return $this->saturdayEnd;
        }

        return $this->endTime;
    }

    private function getDayNameInPortuguese(Carbon $date): string
    {
        $days = [
            'Sunday' => 'domingo',
            'Monday' => 'segunda-feira',
            'Tuesday' => 'terca-feira',
            'Wednesday' => 'quarta-feira',
            'Thursday' => 'quinta-feira',
            'Friday' => 'sexta-feira',
            'Saturday' => 'sabado',
        ];

        return $days[$date->format('l')] ?? $date->format('l');
    }
}
