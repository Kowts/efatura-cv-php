<?php

declare(strict_types=1);

/**
 * Gera uma referência Markdown da API pública a partir de `src/`.
 *
 * O gerador usa `token_get_all()` em vez de reflection para conseguir listar
 * bridges opcionais sem exigir que Laravel, Symfony ou Yii2 estejam instalados.
 */

$root = dirname(__DIR__);
$sourceDir = $root . '/src';
$target = $root . '/docs/api-reference.md';

$symbols = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $symbol = parseFile($file->getPathname(), $root);
    if ($symbol !== null) {
        $symbols[] = $symbol;
    }
}

usort(
    $symbols,
    static fn (array $left, array $right): int => strcmp($left['fqcn'], $right['fqcn'])
);

$markdown = renderMarkdown($symbols);
file_put_contents($target, $markdown);

echo 'API gerada em docs/api-reference.md com ' . count($symbols) . " símbolos.\n";

/**
 * @return null|array{
 *     type:string,
 *     name:string,
 *     namespace:string,
 *     fqcn:string,
 *     summary:string,
 *     file:string,
 *     methods:list<array{name:string, signature:string, summary:string}>
 * }
 */
function parseFile(string $path, string $root): ?array
{
    $tokens = token_get_all((string) file_get_contents($path));
    $namespace = '';
    $symbol = null;
    $lastDocBlock = '';
    $depth = 0;
    $classDepth = null;
    $methods = [];

    for ($i = 0, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];

        if (is_string($token)) {
            if ($token === '{') {
                $depth++;
                if ($symbol !== null && $classDepth === null) {
                    $classDepth = $depth;
                }
            } elseif ($token === '}') {
                $depth--;
            }
            continue;
        }

        [$id, $text] = $token;

        if ($id === T_DOC_COMMENT) {
            $lastDocBlock = $text;
            continue;
        }

        if ($id === T_NAMESPACE) {
            [$namespace, $i] = readNamespace($tokens, $i + 1);
            continue;
        }

        if (
            $depth === 0
            && in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)
            && !isAnonymousClass($tokens, $i)
        ) {
            $name = readNextIdentifier($tokens, $i + 1);
            if ($name === null) {
                continue;
            }
            $type = match ($id) {
                T_CLASS => 'Classe',
                T_INTERFACE => 'Interface',
                T_TRAIT => 'Trait',
                T_ENUM => 'Enum',
                default => 'Símbolo',
            };
            $fqcn = ltrim($namespace . '\\' . $name, '\\');
            $symbol = [
                'type' => $type,
                'name' => $name,
                'namespace' => $namespace,
                'fqcn' => $fqcn,
                'summary' => docSummary($lastDocBlock),
                'file' => normalisePath(substr($path, strlen($root) + 1)),
                'methods' => [],
            ];
            $lastDocBlock = '';
            $classDepth = null;
            continue;
        }

        if ($symbol !== null && $id === T_PUBLIC && $classDepth !== null && $depth === $classDepth) {
            $method = readPublicMethod($tokens, $i, $lastDocBlock);
            if ($method !== null) {
                $methods[] = $method;
            }
            $lastDocBlock = '';
        }
    }

    if ($symbol === null) {
        return null;
    }

    $symbol['methods'] = $methods;

    return $symbol;
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return array{string,int}
 */
function readNamespace(array $tokens, int $offset): array
{
    $parts = [];
    for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_string($token) && ($token === ';' || $token === '{')) {
            return [implode('', $parts), $i];
        }
        if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
            $parts[] = $token[1];
        }
    }

    return [implode('', $parts), $offset];
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 */
function readNextIdentifier(array $tokens, int $offset): ?string
{
    for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_STRING) {
            return $token[1];
        }
        if (is_string($token) && $token === '{') {
            return null;
        }
    }

    return null;
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 */
function isAnonymousClass(array $tokens, int $index): bool
{
    for ($i = $index - 1; $i >= 0; $i--) {
        $token = $tokens[$i];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return is_array($token) && $token[0] === T_NEW;
    }

    return false;
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return null|array{name:string, signature:string, summary:string}
 */
function readPublicMethod(array $tokens, int $publicIndex, string $docBlock): ?array
{
    $signature = '';
    $name = null;
    $seenFunction = false;

    for ($i = $publicIndex, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];
        $text = is_array($token) ? $token[1] : $token;

        if ($text === '{' || $text === ';') {
            break;
        }

        if (is_array($token) && $token[0] === T_FUNCTION) {
            $seenFunction = true;
        } elseif ($seenFunction && is_array($token) && $token[0] === T_STRING) {
            $name = $token[1];
            $seenFunction = false;
        }

        $signature .= $text;
    }

    if ($name === null) {
        return null;
    }

    return [
        'name' => $name,
        'signature' => cleanSignature($signature),
        'summary' => docSummary($docBlock),
    ];
}

function docSummary(string $docBlock): string
{
    if ($docBlock === '') {
        return '';
    }

    $lines = preg_split('/\R/', $docBlock) ?: [];
    $summary = [];
    foreach ($lines as $line) {
        $line = trim((string) preg_replace('/^\s*\/?\*+\s?/', '', $line));
        $line = trim($line, "*/ \t");
        if ($line === '' && $summary !== []) {
            break;
        }
        if ($line === '' || str_starts_with($line, '@')) {
            continue;
        }
        $summary[] = $line;
    }

    return implode(' ', $summary);
}

function cleanSignature(string $signature): string
{
    $signature = preg_replace('/\s+/', ' ', trim($signature)) ?? trim($signature);
    $signature = str_replace('( ', '(', $signature);
    $signature = str_replace(' )', ')', $signature);
    $signature = str_replace(' ,', ',', $signature);
    $signature = str_replace(' :', ':', $signature);

    return $signature;
}

function normalisePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

/**
 * @param list<array{
 *     type:string,
 *     name:string,
 *     namespace:string,
 *     fqcn:string,
 *     summary:string,
 *     file:string,
 *     methods:list<array{name:string, signature:string, summary:string}>
 * }> $symbols
 */
function renderMarkdown(array $symbols): string
{
    $lines = [
        '# Referência automática da API',
        '',
        '> Ficheiro gerado automaticamente. Não edite manualmente.',
        '>',
        '> Para regenerar: `composer docs:api`.',
        '',
        'Esta referência lista símbolos públicos em `src/` e os métodos públicos',
        'declarados em cada classe, interface, trait ou enum.',
        '',
        '## Índice',
        '',
    ];

    foreach ($symbols as $symbol) {
        $lines[] = '- [`' . $symbol['fqcn'] . '`](#' . anchor($symbol['fqcn']) . ')';
    }

    foreach ($symbols as $symbol) {
        $lines[] = '';
        $lines[] = '## `' . $symbol['fqcn'] . '`';
        $lines[] = '';
        $lines[] = '- Tipo: ' . $symbol['type'];
        $lines[] = '- Ficheiro: `' . $symbol['file'] . '`';
        if ($symbol['summary'] !== '') {
            $lines[] = '- Resumo: ' . $symbol['summary'];
        }

        if ($symbol['methods'] === []) {
            continue;
        }

        $lines[] = '';
        $lines[] = '### Métodos públicos';
        $lines[] = '';

        foreach ($symbol['methods'] as $method) {
            $lines[] = '#### `' . $method['name'] . '()`';
            $lines[] = '';
            $lines[] = '```php';
            $lines[] = $method['signature'];
            $lines[] = '```';
            if ($method['summary'] !== '') {
                $lines[] = '';
                $lines[] = $method['summary'];
            }
            $lines[] = '';
        }
    }

    return rtrim(implode("\n", $lines)) . "\n";
}

function anchor(string $value): string
{
    $anchor = strtolower($value);
    $anchor = str_replace('\\', '', $anchor);
    $anchor = preg_replace('/[^a-z0-9 -]/', '', $anchor) ?? $anchor;

    return str_replace(' ', '-', $anchor);
}
