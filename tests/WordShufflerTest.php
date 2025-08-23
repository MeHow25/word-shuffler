<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../WordShuffler.php';

class WordShufflerTest extends TestCase
{
    private WordShuffler $shuffler;

    protected function setUp(): void
    {
        $this->shuffler = new WordShuffler();
    }

    public function testShortWordsRemainUnchanged(): void
    {
        $shortWords = [
            'a',
            'ab',
            'abc',
            'to',
            'i',
            'że'
        ];

        foreach ($shortWords as $word) {
            $result = $this->callPrivateMethod('shuffleWord', [$word]);
            $this->assertEquals(
                $word,
                $result,
                "Short word '{$word}' should remain unchanged"
            );
        }
    }

    public function testWordShufflingPreservesFirstAndLastLetter(): void
    {
        $testWords = [
            'programming',
            'testing',
            'development',
            'computers',
            'algorithm'
        ];

        foreach ($testWords as $word) {
            $result = $this->callPrivateMethod('shuffleWord', [$word]);
            
            $this->assertEquals(
                $word[0],
                $result[0],
                "First letter should be preserved for word '{$word}'"
            );
            
            $this->assertEquals(
                $word[strlen($word) - 1],
                $result[strlen($result) - 1],
                "Last letter should be preserved for word '{$word}'"
            );
            
            $this->assertEquals(
                strlen($word),
                strlen($result),
                "Word length should be preserved for word '{$word}'"
            );
        }
    }

    public function testPolishCharacterSupport(): void
    {
        $polishWords = [
            'ąęćłńóśźż',
            'świat',
            'człowiek',
            'książka',
            'żółć'
        ];

        foreach ($polishWords as $word) {
            if (mb_strlen($word, 'UTF-8') > 3) {
                $result = $this->callPrivateMethod('shuffleWord', [$word]);
                
                $this->assertEquals(
                    mb_substr($word, 0, 1, 'UTF-8'),
                    mb_substr($result, 0, 1, 'UTF-8'),
                    "First Polish character should be preserved for word '{$word}'"
                );
                
                $this->assertEquals(
                    mb_substr($word, -1, 1, 'UTF-8'),
                    mb_substr($result, -1, 1, 'UTF-8'),
                    "Last Polish character should be preserved for word '{$word}'"
                );
            } else {
                $result = $this->callPrivateMethod('shuffleWord', [$word]);
                $this->assertEquals(
                    $word,
                    $result,
                    "Short Polish word '{$word}' should remain unchanged"
                );
            }
        }
    }

    public function testWordsWithPunctuation(): void
    {
        $wordsWithPunctuation = [
            'hello!',
            '(world)',
            '"testing"',
            'word-hyphen',
            'text.',
            ',comma',
            '!!!exclamation!!!'
        ];

        foreach ($wordsWithPunctuation as $word) {
            $result = $this->callPrivateMethod('processWord', [$word]);
            
            $this->assertNotEmpty(
                $result,
                "Processed word should not be empty for '{$word}'"
            );
            
            // Check that the length is preserved
            $this->assertEquals(
                mb_strlen($word, 'UTF-8'),
                mb_strlen($result, 'UTF-8'),
                "Word length should be preserved for punctuated word '{$word}'"
            );
        }
    }

    public function testEmptyAndNullInputs(): void
    {
        // Test empty string
        $result = $this->callPrivateMethod('processWord', ['']);
        $this->assertEquals('', $result, "Empty string should remain empty");
        
        // Test string with only spaces
        $result = $this->callPrivateMethod('processWord', ['   ']);
        $this->assertEquals('   ', $result, "Spaces-only string should remain unchanged");
        
        // Test string with only punctuation
        $result = $this->callPrivateMethod('processWord', ['!!!']);
        $this->assertEquals('!!!', $result, "Punctuation-only string should remain unchanged");
    }

    public function testLineProcessing(): void
    {
        $testLines = [
            'This is a simple test line.',
            'Another line with multiple words here.',
            'Line with numbers 123 and symbols @#$',
            ''
        ];

        foreach ($testLines as $line) {
            $result = $this->callPrivateMethod('processLine', [$line]);
            
            // Count words before and after processing
            $originalWords = empty(trim($line)) ? 0 : count(explode(' ', $line));
            $resultWords = empty(trim($result)) ? 0 : count(explode(' ', $result));
            
            $this->assertEquals(
                $originalWords,
                $resultWords,
                "Number of words should be preserved for line '{$line}'"
            );
        }
    }

    public function testFileProcessingWithValidFiles(): void
    {
        // Create temporary test files
        $inputFile = tempnam(sys_get_temp_dir(), 'test_input_');
        $outputFile = tempnam(sys_get_temp_dir(), 'test_output_');
        
        $testContent = "This is a test file.\nWith multiple lines.\nContaining various words.";
        file_put_contents($inputFile, $testContent);

        try {
            $result = $this->shuffler->processFile($inputFile, $outputFile);
            
            $this->assertTrue($result, "processFile should return true on success");
            $this->assertFileExists($outputFile, "Output file should be created");
            
            $outputContent = file_get_contents($outputFile);
            $this->assertNotEmpty($outputContent, "Output file should not be empty");
            
            // Check that number of lines is preserved
            $originalLines = explode("\n", $testContent);
            $outputLines = explode("\n", $outputContent);
            $this->assertEquals(
                count($originalLines),
                count($outputLines),
                "Number of lines should be preserved"
            );
            
        } finally {
            // Clean up temporary files
            if (file_exists($inputFile)) unlink($inputFile);
            if (file_exists($outputFile)) unlink($outputFile);
        }
    }

    public function testFileProcessingWithNonExistentFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("does not exist");
        
        $nonExistentFile = '/path/to/non/existent/file.txt';
        $outputFile = tempnam(sys_get_temp_dir(), 'test_output_');
        
        try {
            $this->shuffler->processFile($nonExistentFile, $outputFile);
        } finally {
            if (file_exists($outputFile)) unlink($outputFile);
        }
    }

    public function testFileProcessingWithUnreadableFile(): void
    {
        // Test with a path that doesn't exist in /proc to simulate permission denied
        $unreadableFile = '/proc/non_existent_file_' . rand(1000, 9999);
        $outputFile = tempnam(sys_get_temp_dir(), 'test_output_');
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("does not exist");
        
        try {
            $this->shuffler->processFile($unreadableFile, $outputFile);
        } finally {
            if (file_exists($outputFile)) unlink($outputFile);
        }
    }

    public function testIsLetterMethod(): void
    {
        $letters = ['a', 'Z', 'ą', 'ę', 'ć', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'];
        $nonLetters = ['1', '2', '!', '@', '#', '$', ' ', '-', '.', ','];

        foreach ($letters as $char) {
            $result = $this->callPrivateMethod('isLetter', [$char]);
            $this->assertTrue(
                (bool)$result,
                "Character '{$char}' should be recognized as a letter"
            );
        }

        foreach ($nonLetters as $char) {
            $result = $this->callPrivateMethod('isLetter', [$char]);
            $this->assertFalse(
                (bool)$result,
                "Character '{$char}' should not be recognized as a letter"
            );
        }
    }

    public function testShufflingActuallyChangesWords(): void
    {
        $longWords = [
            'programming',
            'development',
            'testing',
            'computer',
            'algorithm',
            'implementation'
        ];

        $changedCount = 0;
        $totalAttempts = 0;

        foreach ($longWords as $word) {
            // Try multiple times since shuffling might occasionally return the same result
            for ($i = 0; $i < 10; $i++) {
                $result = $this->callPrivateMethod('shuffleWord', [$word]);
                $totalAttempts++;
                
                if ($result !== $word) {
                    $changedCount++;
                    break; // At least one shuffle changed the word, move to next word
                }
            }
        }

        // At least some words should change after shuffling
        $this->assertGreaterThan(
            0,
            $changedCount,
            "At least some long words should change after shuffling"
        );
    }

    public function testComplexTextProcessing(): void
    {
        $complexText = "Hello, world! This is a test (with parentheses) and \"quotes\". Numbers: 123, 456.";
        
        $result = $this->callPrivateMethod('processLine', [$complexText]);
        
        $this->assertNotEmpty($result, "Complex text should not become empty");
        
        // Check that punctuation marks are still present
        $this->assertStringContainsString(',', $result, "Commas should be preserved");
        $this->assertStringContainsString('!', $result, "Exclamation marks should be preserved");
        $this->assertStringContainsString('(', $result, "Parentheses should be preserved");
        $this->assertStringContainsString('"', $result, "Quotes should be preserved");
        $this->assertStringContainsString(':', $result, "Colons should be preserved");
    }

    /**
     * Helper method to call private methods for testing
     */
    private function callPrivateMethod($methodName, $parameters = [])
    {
        $reflection = new ReflectionClass($this->shuffler);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($this->shuffler, $parameters);
    }
}
