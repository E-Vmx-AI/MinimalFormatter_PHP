<?php

/**
 * Класс для минимального форматирования текста, 
 * обрабатывающий LaTeX, Markdown-bold и переводы строк.
 */
class MinimalFormatter
{
    /**
     * Основной публичный метод форматирования.
     */
    public function format(string $s): string 
    {
        // 1-3. Подготовка
        $s = str_replace('&#92;', '\\', $s);
        $s = str_replace(['<strong>','</strong>'], ["\x01S\x01","\x01s\x01"], $s);
        $parts = preg_split('/(\\\\\((?:.|\R)*?\\\\\)|\\\\\[(?:.|\R)*?\\\\\])/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);

        // 4. Сборка результата
        $out = '';
        foreach ($parts as $chunk) {
            if ($chunk === '') continue;
            
            $isMath = (strlen($chunk) > 4 && $chunk[0] === '\\' && ($chunk[1] === '(' || $chunk[1] === '['));
            
            if ($isMath) {
                $inner = substr($chunk, 2, -2);
                $html  = $this->renderLatex($inner); 
                $out  .= ($chunk[1] === '(')
                    ? '<span class="math-inline">'.$html.'</span>'
                    : '<div class="math-display" style="display:block;text-align:center;margin:0.4em 0;">'.$html.'</div>';
            } else {
                $out .= htmlspecialchars($chunk, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        // 5-7. Финализация
        $out = str_replace(["\x01S\x01","\x01s\x01"], ['<strong>','</strong>'], $out);
        $out = nl2br($out, false);
        $out = preg_replace_callback('/\*\*([^*<>]+)\*\*/us', function($m) {
            return '<strong>' . $m[1] . '</strong>';
        }, $out);

        return $out;
    }

    /**
     * Рекурсивный приватный метод для рендеринга LaTeX в HTML.
     */
    private function renderLatex(string $tex): string
    {
        $tex = trim($tex);
        
        // --- 1. Обработка delimiters, пробелов и греческих букв ---
        $tex = preg_replace(
            [
                '/\\\\left\\./u','/\\\\right\\./u', '/\\\\left\\(/u','/\\\\right\\)/u',
                '/\\\\left\\[/u','/\\\\right\\]/u', '/\\\\left\\{/u','/\\\\right\\}/u',
                '/\\\\left\\|/u','/\\\\right\\|/u', '/\\\\left\\\\\\|/u','/\\\\right\\\\\\|/u',
                '/\\\\langle/u','/\\\\rangle/u', '/\\\\lfloor/u','/\\\\rfloor/u',
                '/\\\\lceil/u','/\\\\rceil/u'
            ],
            [
                '', '', '(', ')', '[', ']', '{', '}', '|', '|',
                '&#x2225;', '&#x2225;', '&lang;', '&rang;', '&lfloor;', '&rfloor;', '&lceil;', '&rceil;'
            ],
            $tex
        );

         $tex = preg_replace('/\\\\,|\\\\;|\\\\!|\\\\quad|\\\\qquad/u', ' ', $tex);
         $tex = str_replace(
            ['\cdot','\times','\pm', '\alpha','\beta','\gamma','\Gamma', '\delta','\Delta','\epsilon','\varepsilon', '\zeta','\eta','\theta','\Theta', '\iota','\kappa','\lambda','\Lambda', '\mu','\nu','\xi','\Xi', '\pi','\Pi','\rho','\sigma','\Sigma', '\tau','\upsilon','\Upsilon', '\phi','\varphi','\Phi','\chi', '\psi','\Psi','\omega','\Omega' ],
            ['&middot;','&times;','&plusmn;', '&alpha;','&beta;','&gamma;','&Gamma;', '&delta;','&Delta;','&epsilon;','&varepsilon;', '&zeta;','&eta;','&theta;','&Theta;', '&iota;','&kappa;','&lambda;','&Lambda;', '&mu;','&nu;','&xi;','&Xi;', '&pi;','&Pi;','&rho;','&sigma;','&Sigma;', '&tau;','\upsilon;','&Upsilon;', '&phi;','&varphi;','&Phi;','&chi;', '&psi;','\omega;','&Omega;'],
            $tex
        );

        // --- 2. Обработка \text{...} ---
        $tex = preg_replace_callback('/\\\\text\s*\{((?:[^{}]+|(?R))*)\}/u', function($m){
            $inner = $m[1];
            $safeInner = htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeInner = strtr($safeInner, ['_' => '&#95;', '^' => '&#94;', '\\' => '' ]);
            return '<span class="math-text">'.$safeInner.'</span>';
        }, $tex);

        // --- 3. РЕФАКТОРИНГ: Рекурсивная обработка \frac{num}{den} ---
        // (?R) позволяет рекурсивно искать сбалансированные скобки внутри числителя и знаменателя.
        $tex = preg_replace_callback(
            '/\\\\frac\s*\{((?:[^{}]+|(?R))*)\}\s*\{((?:[^{}]+|(?R))*)\}/u', 
            function($m) {
                // $m[1] - числитель (num), $m[2] - знаменатель (den)
                $numH = $this->renderLatex($m[1]); // Рекурсивный вызов!
                $denH = $this->renderLatex($m[2]); // Рекурсивный вызов!

                return '<span style="display:inline-block;vertical-align:middle;text-align:center;">'
                     . '<span style="display:block;border-bottom:1px solid currentColor;">'.$numH.'</span>'
                     . '<span style="display:block;">'.$denH.'</span>'
                     . '</span>';
            }, 
            $tex
        );

        // --- 4. Обработка индексов/степеней ---
        $tex = preg_replace_callback('/(_|\^)\s*\{([^{}]*)\}/u', function($m){
            $tag = ($m[1] === '_') ? 'sub' : 'sup';
            return '<'.$tag.'>'.$this->renderLatex($m[2]).'</'.$tag.'>';
        }, $tex);
        $tex = preg_replace('/_\s*([A-Za-z0-9])/', '<sub>$1</sub>', $tex);
        $tex = preg_replace('/\^\s*([A-Za-z0-9])/', '<sup>$1</sup>', $tex);

        return preg_replace('/\s+/u', ' ', $tex);
    }

    // Приватный метод extractBraced удален.
}
