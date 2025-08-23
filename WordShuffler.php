<?php

class WordShuffler {
    public function processFile($inputFile, $outputFile) {
        if (!file_exists($inputFile)) {
            throw new Exception("File '$inputFile' does not exist!");
        }
        
        $content = file_get_contents($inputFile);
        if ($content === false) {
            throw new Exception("Cannot read file '$inputFile'!");
        }
        
        $lines = explode("\n", $content);
        $processedLines = [];
        
        foreach ($lines as $line) {
            $processedLines[] = $this->processLine($line);
        }
        
        $processedContent = implode("\n", $processedLines);
        
        if (file_put_contents($outputFile, $processedContent) === false) {
            throw new Exception("Cannot write to file '$outputFile'!");
        }
        
        return true;
    }
    
    private function isLetter($char) {
        return preg_match('/\p{L}/u', $char);
    }
    
    private function shuffleWord($word) {
        if (mb_strlen($word, 'UTF-8') <= 3) {
            return $word;
        }
        
        $chars = mb_str_split($word, 1, 'UTF-8');
        $length = count($chars);
        
        $middle = array_slice($chars, 1, $length - 2);
        
        shuffle($middle);
        
        $result = $chars[0]; // first letter
        $result .= implode('', $middle); // shuffled middle letters
        $result .= $chars[$length - 1]; // last letter
        
        return $result;
    }
    
    private function processWord($word) {
        if (empty($word)) {
            return $word;
        }
        
        $start = 0;
        $end = mb_strlen($word, 'UTF-8') - 1;
        
        while ($start <= $end && !$this->isLetter(mb_substr($word, $start, 1, 'UTF-8'))) {
            $start++;
        }
        
        while ($end >= $start && !$this->isLetter(mb_substr($word, $end, 1, 'UTF-8'))) {
            $end--;
        }
        
        if ($start > $end) {
            return $word;
        }
        
        $prefix = mb_substr($word, 0, $start, 'UTF-8');
        $letters = mb_substr($word, $start, $end - $start + 1, 'UTF-8');
        $suffix = mb_substr($word, $end + 1, null, 'UTF-8');
        
        $shuffledLetters = $this->shuffleWord($letters);
        
        return $prefix . $shuffledLetters . $suffix;
    }
    
    private function processLine($line) {
        $words = explode(' ', $line);
        $processedWords = [];
        
        foreach ($words as $word) {
            $processedWords[] = $this->processWord($word);
        }
        
        return implode(' ', $processedWords);
    }
}

function main() {
    global $argv;
    
    if (!isset($argv) || count($argv) != 3) {
        echo "Usage: php WordShuffler.php <input_file> <output_file>\n";
        echo "Example: php WordShuffler.php text.txt shuffled_text.txt\n";
        exit(1);
    }
    
    $inputFile = $argv[1];
    $outputFile = $argv[2];
    
    try {
        $shuffler = new WordShuffler();
        $shuffler->processFile($inputFile, $outputFile);
        echo "File has been successfully processed!\n";
        echo "Result saved to: $outputFile\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    main();
}

?>
