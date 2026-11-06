<?php

namespace App\Console\Commands;

use App\Services\Discord\AttendanceDiscordService;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('discord:bot')]
#[Description('Inicia el bot de Discord para PractiControl')]
class DiscordBotCommand extends Command
{
    public function handle(): int
    {
        $token = config('services.discord.token');

        if (!$token) {
            $this->error('No se encontró el token de Discord.');

            return self::FAILURE;
        }

        $discord = new Discord([
            'token' => $token,
            'intents' => Intents::getDefaultIntents()
                | Intents::GUILD_PRESENCES,
        ]);

        $attendanceService = app(AttendanceDiscordService::class);

        $discord->on(Event::READY, function (Discord $discord) {
            $this->info('Bot conectado correctamente.');
            $this->info('Usuario: ' . $discord->user->username);
        });

        // Detectar cambios en el estado de presencia.
        $discord->on(Event::PRESENCE_UPDATE, function ($presence) use ($attendanceService) {

            $discordId = $presence->user->id ?? null;
            $status = $presence->status ?? null;

            if (!$discordId || !$status) {
                return;
            }

            $this->line("Discord ID: {$discordId} | Estado: {$status}");

            $attendanceService->processPresence(
                (string) $discordId,
                $status
            );
        });

        $discord->run();

        return self::SUCCESS;
    }
}
