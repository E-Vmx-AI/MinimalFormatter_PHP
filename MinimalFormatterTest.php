<?php

require_once 'format_minimal.php';

// ИСПРАВЛЕНИЕ: Используем одинарный слэш (\), как того требует PHP
use PHPUnit\Framework\TestCase;

final class MinimalFormatterTest extends TestCase
{
    private function format_minimal(string $s): string
    {
        return format_minimal($s);
    }
    
    private function normalize_html(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public function testPlainTextIsHtmlEscaped(): void
    {
        $input = 'Text with <tags> & ampersand.';
        $expected = 'Text with &lt;tags&gt; &amp; ampersand.';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testNewlineIsConvertedToBr(): void
    {
        $input = "Line 1\nLine 2";
        $expected = 'Line 1<br>' . "\n" . 'Line 2'; 
        $this->assertSame($expected, $this->format_minimal($input));
    }

    public function testMarkdownBoldIsConvertedToStrong(): void
    {
        $input = 'This is **bold text** and **another**.';
        $expected = 'This is <strong>bold text</strong> and <strong>another</strong>.';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testInlineLatexBasicFraction(): void
    {
        $input = 'The value is \( \frac{1}{2} \).';
        $expected = 'The value is <span class="math-inline"><span style="display:inline-block;vertical-align:middle;text-align:center;"><span style="display:block;border-bottom:1px solid currentColor;">1</span><span style="display:block;">2</span></span></span>.';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testDisplayLatexExponentAndSubscript(): void
    {
        $input = 'Formula: \[ E=m c^2 + v_{max} \]';
        $expected = 'Formula: <div class="math-display" style="display:block;text-align:center;margin:0.4em 0;">E=m c<sup>2</sup> + v<sub>max</sub></div>';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testLatexGreekLettersAndSymbols(): void
    {
        $input = 'Math: \( \alpha \times \beta \pm \pi \) ';
        $expected = 'Math: <span class="math-inline">&alpha; &times; &beta; &plusmn; &pi;</span> ';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testHtmlInsideMarkdownBoldIsSafe(): void
    {
        $input = '**Text with <script>alert(1)</script> tags**';
        $expected = '<strong>Text with &lt;script&gt;alert(1)&lt;/script&gt; tags</strong>';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testPreExistingStrongTagPreservation(): void
    {
        $input = 'Already <strong>strong</strong> text with **new bold** and \( E=mc^2 \).';
        $expectedMath = '<span class="math-inline">E=mc<sup>2</sup></span>';
        $expected = 'Already <strong>strong</strong> text with <strong>new bold</strong> and ' . $expectedMath . '.';
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }


    public function testLatexSquareRootAndDot(): void
    {
        $input = 'Velocity vector: \( \dot{x} \), Magnitude: \( \sqrt{ \frac{1}{x} } \).';
        
        $expectedDot = '<span style="display:inline-block;">&dot;x</span>';
        $expectedFrac = '<span style="display:inline-block;vertical-align:middle;text-align:center;"><span style="display:block;border-bottom:1px solid currentColor;">1</span><span style="display:block;">x</span></span>';
        
        $expectedSqrt = '<span style="display:inline-block; vertical-align:middle; padding: 2px 0;">&#x221A; <span style="border-top: 1px solid currentColor; padding-left: 1px;">' . $expectedFrac . '</span></span>';

        $expected = "Velocity vector: <span class=\"math-inline\">{$expectedDot}</span>, Magnitude: <span class=\"math-inline\">{$expectedSqrt}</span>.";
        
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

    public function testLatexNewComparisonsAndSetTheory(): void
    {
        $input = 'Condition: \( 5 \le x \le 10 \), Solution is in \( \mathbb{R} \).';
        $expected = 'Condition: <span class="math-inline">5 &le; x &le; 10</span>, Solution is in <span class="math-inline">\mathbb{R}</span>.';

        $input2 = 'Limit: \( \lim \frac{1}{n} = 0 \), Ratio: \( a \div b \approx \infty \).';
        
        $expected2 = 'Limit: <span class="math-inline"><span class="op">lim</span> <span style="display:inline-block;vertical-align:middle;text-align:center;"><span style="display:block;border-bottom:1px solid currentColor;">1</span><span style="display:block;">n</span></span> = 0</span>, Ratio: <span class="math-inline">a &divide; b &asymp; &infin;</span>.';
        
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
        $this->assertSame($this->normalize_html($expected2), $this->normalize_html($this->format_minimal($input2)));
    }
    
    public function testLatexPartialDerivative(): void
    {
        $input = 'The partial derivative is \( \frac{\partial f}{\partial x} \) or \( \nabla f \).';
        
        $expectedFrac = '<span style="display:inline-block;vertical-align:middle;text-align:center;"><span style="display:block;border-bottom:1px solid currentColor;">&part; f</span><span style="display:block;">&part; x</span></span>';
        $expected = 'The partial derivative is <span class="math-inline">'.$expectedFrac.'</span> or <span class="math-inline">&nabla; f</span>.';
        
        $this->assertSame($this->normalize_html($expected), $this->normalize_html($this->format_minimal($input)));
    }

}
