<?php

declare(strict_types=1);

namespace Modules\Attendance\Libraries;

use lbuchs\WebAuthn\WebAuthn;
use Modules\Attendance\Models\ChallengeModel;

final class WebAuthnSupport
{
    public static function server(): WebAuthn
    {
        $origin = self::expectedOrigin();
        $host = parse_url($origin, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new \RuntimeException('Impossible de déterminer le domaine WebAuthn. Vérifie app.baseURL / ATTENDANCE_EXPECTED_ORIGIN.');
        }
        $rpId = trim((string) env('ATTENDANCE_RP_ID', $host));
        if ($rpId === '' || !hash_equals(strtolower($host), strtolower($rpId))) {
            throw new \RuntimeException('ATTENDANCE_RP_ID doit être exactement le hostname de ATTENDANCE_EXPECTED_ORIGIN (sans https:// ni chemin).');
        }
        return new WebAuthn('Présence Étudiants', $rpId, ['none'], true);
    }

    public static function expectedOrigin(): string
    {
        $configured = trim((string) env('ATTENDANCE_EXPECTED_ORIGIN', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $base = (string) (config('App')->baseURL ?? base_url());
        $scheme = strtolower((string) parse_url($base, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($base, PHP_URL_HOST));
        $port = parse_url($base, PHP_URL_PORT);
        if ($scheme === '' || $host === '') {
            throw new \RuntimeException('app.baseURL doit contenir le schéma et le domaine, par exemple https://presence.exemple.mg/.');
        }
        $defaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        return $scheme . '://' . $host . ($port && !$defaultPort ? ':' . $port : '');
    }

    /** Exact-origin validation before handing data to the library (strict host boundary). */
    public static function assertClientOrigin(string $clientDataJson): void
    {
        $clientData = json_decode($clientDataJson, true);
        if (!is_array($clientData) || ($clientData['type'] ?? null) === null || !isset($clientData['origin'])) {
            throw new \RuntimeException('Données WebAuthn invalides.');
        }
        $origin = rtrim((string) $clientData['origin'], '/');
        $expected = self::expectedOrigin();
        if (!hash_equals(strtolower($expected), strtolower($origin))) {
            throw new \RuntimeException('Origine WebAuthn inattendue. Vérifie l’URL publique et app.baseURL.');
        }
        $scheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
        if ($scheme !== 'https' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new \RuntimeException('WebAuthn exige HTTPS hors localhost.');
        }
    }

    public static function jsonOptions(object $options): array
    {
        $decoded = json_decode(json_encode($options, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !isset($decoded['publicKey']['challenge'])) {
            throw new \RuntimeException('Options WebAuthn inattendues.');
        }
        $decoded['attendanceChallenge'] = $decoded['publicKey']['challenge'];
        return $decoded;
    }

    public static function decode(string $value): string
    {
        $result = ChallengeModel::base64UrlDecode($value);
        if ($result === null) {
            throw new \RuntimeException('Champ binaire WebAuthn invalide.');
        }
        return $result;
    }
}
