<?php

namespace SunAsterisk\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Common;

/**
 * Report variables not in camel case
 */
class CamelCaseVariableSniff extends AbstractVariableSniff
{
    public const ERR_NOT_CAMEL_CASE = 'NotCamelCase';
    public const ERR_PROPERTY_NOT_CAMEL_CASE = 'PropertyNotCamelCase';

    /**
     * Processes variables
     *
     * @param File $phpcsFile The file being scanned
     * @param int  $stackPtr  Current token position
     */
    protected function processVariable(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        if ($this->isPHPReservedVar($varName) || Common::isCamelCaps($varName, false, true, false)) {
            return;
        }

        $error = 'Variable "%s" is not in camel case';
        $phpcsFile->addError($error, $stackPtr, self::ERR_NOT_CAMEL_CASE, [$varName]);
    }

    /**
     * Processes class member variables
     *
     * @param File $phpcsFile The file being scanned
     * @param int  $stackPtr  Current token position
     */
    protected function processMemberVar(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        if (!Common::isCamelCaps($varName, false, true, false)) {
            $error = 'Property "%s" is not in camel case';
            $phpcsFile->addError($error, $stackPtr, self::ERR_PROPERTY_NOT_CAMEL_CASE, [$varName]);
        }
    }

    /**
     * Processes variables found within a double quoted string
     *
     * @param File $phpcsFile The file being scanned
     * @param int  $stackPtr  Current token position
     */
    protected function processVariableInString(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!preg_match_all('|[^\\\]\${?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|', $tokens[$stackPtr]['content'], $matches)) {
            return;
        }

        foreach ($matches[1] as $varName) {
            if ($this->isPHPReservedVar($varName) || Common::isCamelCaps($varName, false, true, false)) {
                continue;
            }

            $error = 'Variable "%s" is not in camel case';
            $phpcsFile->addError($error, $stackPtr, self::ERR_NOT_CAMEL_CASE, [$varName]);
        }
    }

    private function isPHPReservedVar(string $varName): bool
    {
        return isset($this->phpReservedVars[$varName]);
    }
}
