<?php

namespace App\Support;

use Illuminate\Http\Request;

final class ValidatedListReturn
{
    private function __construct(
        private readonly string $target,
        private readonly bool $carried,
    ) {
    }

    public static function fromRequest(Request $request, string $expectedPath): self
    {
        [$candidate, $carried] = self::candidateFromRequest($request);

        return new self(self::validate($candidate, $expectedPath), $carried);
    }

    public static function validate(mixed $candidate, string $expectedPath): string
    {
        if (! is_string($candidate)
            || $candidate === ''
            || ! str_starts_with($candidate, '/')
            || str_starts_with($candidate, '//')
            || str_contains($candidate, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $candidate) === 1
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $candidate) === 1) {
            return $expectedPath;
        }

        $decoded = rawurldecode($candidate);
        if (str_contains($decoded, '\\') || preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return $expectedPath;
        }

        $parts = parse_url($candidate);
        if ($parts === false
            || isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || ($parts['path'] ?? '') !== $expectedPath) {
            return $expectedPath;
        }

        $query = self::stripReturnTo((string) ($parts['query'] ?? ''));

        return $expectedPath.($query === '' ? '' : '?'.$query);
    }

    public function target(): string
    {
        return $this->target;
    }

    /** @param array<string, mixed> $parameters */
    public function routeParameters(array $parameters): array
    {
        if ($this->carried) {
            $parameters['return_to'] = $this->target;
        }

        return $parameters;
    }

    /** @return array{0: mixed, 1: bool} */
    private static function candidateFromRequest(Request $request): array
    {
        if ($request->has('return_to')) {
            return [$request->input('return_to'), true];
        }

        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return [null, false];
        }

        $parts = parse_url($referer);
        if ($parts === false || ! isset($parts['query'])) {
            return [null, false];
        }

        parse_str($parts['query'], $query);
        if (! array_key_exists('return_to', $query)) {
            return [null, false];
        }

        return [$query['return_to'], true];
    }

    private static function stripReturnTo(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $segments = array_filter(
            explode('&', $query),
            static function (string $segment): bool {
                $rawKey = explode('=', $segment, 2)[0];
                $key = rawurldecode(str_replace('+', ' ', $rawKey));

                return $key !== 'return_to' && ! str_starts_with($key, 'return_to[');
            },
        );

        return implode('&', $segments);
    }
}
