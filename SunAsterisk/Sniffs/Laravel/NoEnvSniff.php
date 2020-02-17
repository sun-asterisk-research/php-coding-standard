<?php

namespace SunAsterisk\Sniffs\Laravel;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Report usage of env()
 * Based on Generic.PHP.ForbiddenFunctions
 */
class NoEnvSniff implements Sniff
{
    private const IGNORED_TOKENS = [
        T_DOUBLE_COLON    => true,
        T_OBJECT_OPERATOR => true,
        T_FUNCTION        => true,
        T_CONST           => true,
        T_PUBLIC          => true,
        T_PRIVATE         => true,
        T_PROTECTED       => true,
        T_AS              => true,
        T_NEW             => true,
        T_INSTEADOF       => true,
        T_NS_SEPARATOR    => true,
        T_IMPLEMENTS      => true,
    ];

    public function register(): array
    {
        return [
            T_STRING,
        ];
    }

    /**
     * @param File $phpcsFile The file being scanned
     * @param int  $stackPtr Current token position
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($tokens[$prevToken]['code'] === T_NS_SEPARATOR) {
            $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $prevToken - 1, null, true);
            if ($tokens[$prevToken]['code'] === T_STRING) {
                // Not in the global namespace.
                return;
            }
        }

        if (isset(self::IGNORED_TOKENS[$tokens[$prevToken]['code']])) {
            // Not a call to a PHP function.
            return;
        }

        $nextToken = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if (isset(self::IGNORED_TOKENS[$tokens[$nextToken]['code']])) {
            // Not a call to a PHP function.
            return;
        }

        if ($tokens[$stackPtr]['code'] === T_STRING && $tokens[$nextToken]['code'] !== T_OPEN_PARENTHESIS) {
            // Not a call to a PHP function.
            return;
        }

        $functionName = strtolower($tokens[$stackPtr]['content']);

        if ($functionName === 'env') {
            $phpcsFile->addError('Using env() outside config is forbidden, use config() instead', $stackPtr, 'Found');
        }
    }
}
