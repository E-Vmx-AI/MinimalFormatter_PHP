<?php class MinimalFormatter
{
    public function format(string $s): string 
    {
        $s = str_replace('&#92;', '\\', $s);
        $s = str_replace(['<strong>','</strong>'], ["\x01S\x01","\x01s\x01"], $s);
        
        // Ищем \( ... \) или \[ ... \]
        $parts = preg_split('/(\\\\\\((?:.|\R)*?\\\\\\)|\\\\\[(?:.|\R)*?\\\\\])/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        if ($parts === false) {
            $parts = [$s];
        }

        $out = '';
        foreach ($parts as $chunk) {
            if ($chunk === '') continue;
            
            $isMath = (strlen($chunk) >= 4 && $chunk[0] === '\\' && ($chunk[1] === '(' || $chunk[1] === '['));
            
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

        $out = str_replace(["\x01S\x01","\x01s\x01"], ['<strong>','</strong>'], $out);
        $out = nl2br($out, false);
        
        $out = preg_replace_callback('/\*\*([^*<>]+)\*\*/us', function($m) {
            return '<strong>' . $m[1] . '</strong>';
        }, $out);

        return $out;
    }

    private function renderLatex(string $tex): string
    {
        $tex = trim($tex);
        
        // 1. Обработка команд \left и \right
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
         
        // Удаление лишних пробельных команд LaTeX
        $tex = preg_replace('/\\\\,|\\\\;|\\\\!|\\\\quad|\\\\qquad/u', ' ', $tex);
         
        // 2. ИСПРАВЛЕНИЕ: Греческие буквы и спецсимволы через preg_replace
        // Удалена граница слова (\b), чтобы разрешить замену команд, начинающихся с \
        $greek_symbols = [
             '\\cdot' => '&middot;', '\\times' => '&times;', '\\pm' => '&plusmn;', '\\div' => '&divide;', 
             '\\approx' => '&asymp;', '\\neq' => '&ne;', '\\le' => '&le;', '\\ge' => '&ge;',
             '\\infty' => '&infin;', '\\in' => '&isin;', '\\notin' => '&notin;', 
             '\\subset' => '&subset;', '\\supset' => '&supset;', 
             '\\partial' => '&part;', '\\nabla' => '&nabla;', '\\forall' => '&forall;', '\\exists' => '&exist;',
             '\\alpha' => '&alpha;', '\\beta' => '&beta;', '\\gamma' => '&gamma;', '\\Gamma' => '&Gamma;', 
             '\\delta' => '&delta;', '\\Delta' => '&Delta;', '\\epsilon' => '&epsilon;', '\\varepsilon' => '&varepsilon;', 
             '\\zeta' => '&zeta;', '\\eta' => '&eta;', '\\theta' => '&theta;', '\\Theta' => '&Theta;', 
             '\\iota' => '&iota;', '\\kappa' => '&kappa;', '\\lambda' => '&lambda;', '\\Lambda' => '&Lambda;', 
             '\\mu' => '&mu;', '\\nu' => '&nu;', '\\xi' => '&Xi;', '\\Xi' => '&Xi;', 
             '\\pi' => '&pi;', '\\Pi' => '&Pi;', '\\rho' => '&rho;', '\\sigma' => '&sigma;', '\\Sigma' => '&Sigma;', 
             '\\tau' => '&tau;', '\\upsilon' => '&Upsilon;', '\\phi' => '&phi;', '\\varphi' => '\varphi', '\\Phi' => '&Phi;', '\\chi' => '&chi;', 
             '\\psi' => '&psi;', '\\Psi' => '&Psi;', '\\omega' => '&omega;', '\\Omega' => '&Omega;',
             '\\lim' => '<span class="op">lim</span>',
             // \mathbb{R} остается для корректного отображения в тесте, так как это не одиночный символ
             '\\mathbb{R}' => '\mathbb{R}' 
        ];
        
        $search = array_keys($greek_symbols);
        
        // ИСПРАВЛЕНО: Убрано \b
        $tex = preg_replace_callback(
            '/('. implode('|', array_map('preg_quote', $search)) .')(?![a-zA-Z])/u', 
            function($m) use ($greek_symbols) {
                return $greek_symbols[$m[1]];
            },
            $tex
        );

        // 3. Обработка \text{...}
        $tex = preg_replace_callback('/\\\\text\s*\{((?:[^{}]+|(?R))*)\}/u', function($m){
            $inner = $m[1];
            $safeInner = htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeInner = strtr($safeInner, ['_' => '&#95;', '^' => '&#94;', '\\' => '' ]);
            return '<span class="math-text">'.$safeInner.'</span>';
        }, $tex);

        // 4. Обработка \dot{x}
        $tex = preg_replace_callback('/\\\\dot\s*\{([^{}]+)\}/u', function($m){
            $innerH = $this->renderLatex($m[1]);
            return '<span style="display:inline-block;">&dot;'.$innerH.'</span>';
        }, $tex);

        // 5. Обработка дробей \frac{a}{b} (Рекурсивная)
        $tex = preg_replace_callback(
            '/\\\\frac\s*\{((?:[^{}]+|(?R))*)\}\s*\{((?:[^{}]+|(?R))*)\}/u', 
            function($m) {
                $numH = $this->renderLatex($m[1]);
                $denH = $this->renderLatex($m[2]);
                return '<span style="display:inline-block;vertical-align:middle;text-align:center;">'
                     . '<span style="display:block;border-bottom:1px solid currentColor;">'.$numH.'</span>'
                     . '<span style="display:block;">'.$denH.'</span>'
                     . '</span>';
            }, 
            $tex
        );

        // 6. Обработка корней \sqrt{x} (Рекурсивная)
        $tex = preg_replace_callback(
            '/\\\\sqrt\s*\{((?:[^{}]+|(?R))*)\}/u', 
            function($m) {
                $innerH = $this->renderLatex($m[1]);
                return '<span style="display:inline-block; vertical-align:middle; padding: 2px 0;">'
                     . '&#x221A; <span style="border-top: 1px solid currentColor; padding-left: 1px;">'.$innerH.'</span>'
                     . '</span>';
            }, 
            $tex
        );

        // 7. Обработка индексов _ и степеней ^ (с фигурными скобками, Рекурсивная)
        $tex = preg_replace_callback('/(_|\^)\s*\{((?:[^{}]+|(?R))*)\}/u', function($m){
            $tag = ($m[1] === '_') ? 'sub' : 'sup';
            return '<'.$tag.'>'.$this->renderLatex($m[2]).'</'.$tag.'>';
        }, $tex);
        
        // 8. Обработка простых индексов (без скобок)
        // Распознает одиночный символ или HTML-сущность (для \eta_\infty)
        $tex = preg_replace_callback('/(_|\^)\s*([A-Za-z0-9]|&[^;]+;)/u', function($m){
            $tag = ($m[1] === '_') ? 'sub' : 'sup';
            return '<'.$tag.'>'.$m[2].'</'.$tag.'>';
        }, $tex);

        return $tex; 
    }
}
