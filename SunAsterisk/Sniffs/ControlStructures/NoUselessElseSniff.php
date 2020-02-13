<?php

namespace SunAsterisk\Sniffs\ControlStructures;

use Exception;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ConditionHelper;
use SlevomatCodingStandard\Helpers\IndentationHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use Throwable;

/**
 * Report useless else/elseif
 * Based on SlevomatCodingStandard.ControlStructures.EarlyExit
 */
class NoUselessElseSniff implements Sniff
{
    public const ERR_USE_EARLY_EXIT = 'UseEarlyExit';
    public const ERR_USELESS_ELSE = 'UselessElse';
    public const ERR_USELESS_ELSEIF = 'UselessElseIf';

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [
            T_ELSE,
            T_ELSEIF,
        ];
    }

    /**
     * @param File $file The file being scanned
     * @param int  $pos  Current token position
     */
    public function process(File $file, $pos): void
    {
        $tokens = $file->getTokens();

        if ($tokens[$pos]['code'] === T_ELSE) {
            $this->processElse($file, $pos);
        } else {
            $this->processElseIf($file, $pos);
        }
    }

    private function processElse(File $phpcsFile, int $elsePointer): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!array_key_exists('scope_opener', $tokens[$elsePointer])) {
            // Else without curly braces is not supported.
            return;
        }

        try {
            $allConditionsPointers = $this->getAllConditionsPointers($phpcsFile, $elsePointer);
        } catch (Throwable $e) {
            // Else without curly braces is not supported.
            return;
        }

        $ifPointer = $allConditionsPointers[0];
        $ifEarlyExitPointer = null;
        $elseEarlyExitPointer = null;
        $previousConditionPointer = null;
        $previousConditionEarlyExitPointer = null;

        foreach ($allConditionsPointers as $conditionPointer) {
            $conditionEarlyExitPointer = $this->findEarlyExitInScope(
                $phpcsFile,
                $tokens[$conditionPointer]['scope_opener'],
                $tokens[$conditionPointer]['scope_closer']
            );

            if ($conditionPointer === $elsePointer) {
                $elseEarlyExitPointer = $conditionEarlyExitPointer;

                continue;
            }

            $previousConditionPointer = $conditionPointer;
            $previousConditionEarlyExitPointer = $conditionEarlyExitPointer;

            if ($conditionPointer === $ifPointer) {
                $ifEarlyExitPointer = $conditionEarlyExitPointer;

                continue;
            }

            if ($conditionEarlyExitPointer === null) {
                return;
            }
        }

        if ($ifEarlyExitPointer === null && $elseEarlyExitPointer === null) {
            return;
        }

        if ($elseEarlyExitPointer !== null && $previousConditionEarlyExitPointer === null) {
            $fix = $phpcsFile->addFixableError(
                'Use early exit instead of else.',
                $elsePointer,
                self::ERR_USE_EARLY_EXIT
            );

            if (!$fix) {
                return;
            }

            $ifCodePointers = $this->getScopeCodePointers($phpcsFile, $ifPointer);
            $elseCode = $this->getScopeCode($phpcsFile, $elsePointer);
            $negativeIfCondition = ConditionHelper::getNegativeCondition(
                $phpcsFile,
                $tokens[$ifPointer]['parenthesis_opener'],
                $tokens[$ifPointer]['parenthesis_closer']
            );
            $afterIfCode = IndentationHelper::fixIndentation($phpcsFile, $ifCodePointers, IndentationHelper::getIndentation($phpcsFile, $ifPointer));

            $phpcsFile->fixer->beginChangeset();

            for ($i = $ifPointer; $i <= $tokens[$elsePointer]['scope_closer']; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->addContent(
                $ifPointer,
                sprintf(
                    'if %s {%s}%s%s',
                    $negativeIfCondition,
                    $elseCode,
                    $phpcsFile->eolChar,
                    $afterIfCode
                )
            );

            $phpcsFile->fixer->endChangeset();

            return;
        }

        if (
            $previousConditionEarlyExitPointer !== null
            && $tokens[$previousConditionEarlyExitPointer]['code'] === T_YIELD
            && $tokens[$elseEarlyExitPointer]['code'] === T_YIELD
        ) {
            return;
        }

        $pointerAfterElseCondition = TokenHelper::findNextEffective($phpcsFile, $tokens[$elsePointer]['scope_closer'] + 1);

        if ($pointerAfterElseCondition === null || $tokens[$pointerAfterElseCondition]['code'] !== T_CLOSE_CURLY_BRACKET) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Remove useless else to reduce code nesting.',
            $elsePointer,
            self::ERR_USELESS_ELSE
        );

        if (!$fix) {
            return;
        }

        $elseCodePointers = $this->getScopeCodePointers($phpcsFile, $elsePointer);
        $afterIfCode = IndentationHelper::fixIndentation($phpcsFile, $elseCodePointers, IndentationHelper::getIndentation($phpcsFile, $ifPointer));

        $phpcsFile->fixer->beginChangeset();

        $phpcsFile->fixer->replaceToken(
            $tokens[$previousConditionPointer]['scope_closer'] + 1,
            sprintf(
                '%s%s',
                $phpcsFile->eolChar,
                $afterIfCode
            )
        );

        for ($i = $tokens[$previousConditionPointer]['scope_closer'] + 2; $i <= $tokens[$elsePointer]['scope_closer']; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    private function processElseIf(File $phpcsFile, int $elseIfPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        try {
            $allConditionsPointers = $this->getAllConditionsPointers($phpcsFile, $elseIfPointer);
        } catch (Throwable $e) {
            // Elseif without curly braces is not supported.
            return;
        }

        $elseIfEarlyExitPointer = null;
        $previousConditionEarlyExitPointer = null;

        foreach ($allConditionsPointers as $conditionPointer) {
            $conditionEarlyExitPointer = $this->findEarlyExitInScope(
                $phpcsFile,
                $tokens[$conditionPointer]['scope_opener'],
                $tokens[$conditionPointer]['scope_closer']
            );

            if ($conditionPointer === $elseIfPointer) {
                $elseIfEarlyExitPointer = $conditionEarlyExitPointer;

                break;
            }

            $previousConditionEarlyExitPointer = $conditionEarlyExitPointer;

            if ($conditionEarlyExitPointer === null) {
                return;
            }
        }

        if (
            $previousConditionEarlyExitPointer !== null
            && $tokens[$previousConditionEarlyExitPointer]['code'] === T_YIELD
            && $elseIfEarlyExitPointer !== null
            && $tokens[$elseIfEarlyExitPointer]['code'] === T_YIELD
        ) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Use if instead of elseif.',
            $elseIfPointer,
            self::ERR_USELESS_ELSEIF
        );

        if (!$fix) {
            return;
        }

        /** @var int $pointerBeforeElseIfPointer */
        $pointerBeforeElseIfPointer = TokenHelper::findPreviousExcluding($phpcsFile, T_WHITESPACE, $elseIfPointer - 1);

        $phpcsFile->fixer->beginChangeset();

        for ($i = $pointerBeforeElseIfPointer + 1; $i < $elseIfPointer; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->addNewline($pointerBeforeElseIfPointer);
        $phpcsFile->fixer->addNewline($pointerBeforeElseIfPointer);

        $phpcsFile->fixer->replaceToken($elseIfPointer, sprintf('%sif', IndentationHelper::getIndentation($phpcsFile, $allConditionsPointers[0])));

        $phpcsFile->fixer->endChangeset();
    }

    private function getScopeCode(File $phpcsFile, int $scopePointer): string
    {
        $tokens = $phpcsFile->getTokens();

        return TokenHelper::getContent($phpcsFile, $tokens[$scopePointer]['scope_opener'] + 1, $tokens[$scopePointer]['scope_closer'] - 1);
    }

    /**
     * @param File $phpcsFile
     * @param int $scopePointer
     * @return int[]
     */
    private function getScopeCodePointers(File $phpcsFile, int $scopePointer): array
    {
        $tokens = $phpcsFile->getTokens();

        return range($tokens[$scopePointer]['scope_opener'] + 1, $tokens[$scopePointer]['scope_closer'] - 1);
    }

    private function findEarlyExitInScope(File $phpcsFile, int $startPointer, int $endPointer): ?int
    {
        $lastSemicolonInScopePointer = TokenHelper::findPreviousEffective($phpcsFile, $endPointer - 1);

        return $phpcsFile->getTokens()[$lastSemicolonInScopePointer]['code'] === T_SEMICOLON
            ? TokenHelper::findPreviousLocal($phpcsFile, TokenHelper::$earlyExitTokenCodes, $lastSemicolonInScopePointer - 1, $startPointer)
            : null;
    }

    /**
     * @param File $phpcsFile
     * @param int $conditionPointer
     * @return int[]
     */
    private function getAllConditionsPointers(File $phpcsFile, int $conditionPointer): array
    {
        $tokens = $phpcsFile->getTokens();

        $conditionsPointers = [$conditionPointer];

        if (isset($tokens[$conditionPointer]['scope_opener']) && $tokens[$tokens[$conditionPointer]['scope_opener']]['code'] === T_COLON) {
            // Alternative control structure syntax.
            throw new Exception(sprintf('"%s" without curly braces is not supported.', $tokens[$conditionPointer]['content']));
        }

        if ($tokens[$conditionPointer]['code'] !== T_IF) {
            $currentConditionPointer = $conditionPointer;

            do {
                $previousConditionCloseParenthesisPointer = TokenHelper::findPreviousEffective($phpcsFile, $currentConditionPointer - 1);
                $currentConditionPointer = $tokens[$previousConditionCloseParenthesisPointer]['scope_condition'];

                $conditionsPointers[] = $currentConditionPointer;
            } while ($tokens[$currentConditionPointer]['code'] !== T_IF);
        }

        if ($tokens[$conditionPointer]['code'] !== T_ELSE) {
            if (!array_key_exists('scope_closer', $tokens[$conditionPointer])) {
                throw new Exception(sprintf('"%s" without curly braces is not supported.', $tokens[$conditionPointer]['content']));
            }

            $currentConditionPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$conditionPointer]['scope_closer'] + 1);

            if ($currentConditionPointer !== null) {
                while (in_array($tokens[$currentConditionPointer]['code'], [T_ELSEIF, T_ELSE], true)) {
                    $conditionsPointers[] = $currentConditionPointer;

                    if (!array_key_exists('scope_closer', $tokens[$currentConditionPointer])) {
                        throw new Exception(sprintf('"%s" without curly braces is not supported.', $tokens[$currentConditionPointer]['content']));
                    }

                    $currentConditionPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$currentConditionPointer]['scope_closer'] + 1);
                }
            }
        }

        sort($conditionsPointers);

        return $conditionsPointers;
    }
}
