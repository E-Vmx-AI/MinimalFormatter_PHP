<?php
/**
 * Класс для минимального форматирования текста,
 * обрабатывающий LaTeX, Markdown-bold и переводы строк.
 */
class MinimalFormatter
{
    public function format(string $s): string
    {
        $s = str_replace('&#92;', '\\', $s);
        $s = str_replace(['<strong>','</strong>'], ["\x01S\x01","\x01s\x01"], $s);
        $parts = preg_split('/(\\\\\((?:.|\R)*?\\\\\)|\\\\\[(?:.|\R)*?\\\\\])/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
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
        $tex = preg_replace(
            [
                '/\\\\left\\./u','/\\\\right\\./u',
                '/\\\\left\\(/u','/\\\\right\\)/u',
                '/\\\\left\\[/u','/\\\\right\\]/u',
                '/\\\\left\\{/u','/\\\\right\\}/u',
                '/\\\\left\\|/u','/\\\\right\\|/u',
                '/\\\\left\\\\\\|/u','/\\\\right\\\\\\|/u',
                '/\\\\langle/u','/\\\\rangle/u',
                '/\\\\lfloor/u','/\\\\rfloor/u',
                '/\\\\lceil/u','/\\\\rceil/u'
            ],
            [
                '', '',
                '(', ')', '[', ']', '{', '}', '|', '|',
                '&#x2225;', '&#x2225;',
                '&lang;', '&rang;', '&lfloor;', '&rfloor;', '&lceil;', '&rceil;'
            ],
            $tex
        );

         $tex = preg_replace('/\\\\,|\\\\;|\\\\!|\\\\quad|\\\\qquad/u', ' ', $tex);
         $tex = str_replace(
            ['\cdot','\times','\pm', '\alpha','\beta','\gamma','\Gamma', '\delta','\Delta','\epsilon','\varepsilon', '\zeta','\eta','\theta','\Theta', '\iota','\kappa','\lambda','\Lambda', '\mu','\nu','\xi','\Xi', '\pi','\Pi','\rho','\sigma','\Sigma', '\tau','\upsilon','\Upsilon', '\phi','\varphi','\Phi','\chi', '\psi','\Psi','\omega','\Omega' ],
            ['&middot;','&times;','&plusmn;', '&alpha;','&beta;','&gamma;','&Gamma;', '&delta;','&Delta;','&epsilon;','&varepsilon;', '&zeta;','&eta;','&theta;','&Theta;', '&iota;','&kappa;','&lambda;','&Lambda;', '&mu;','&nu;','&xi;','&Xi;', '&pi;','&Pi;','&rho;','&sigma;','&Sigma;', '&tau;','&upsilon;','&Upsilon;', '&phi;','&varphi;','&Phi;','&chi;', '&psi;','\omega;','&Omega;'],
            $tex
        );

        $tex = preg_replace_callback('/\\\\text\s*\{((?:[^{}]+|(?R))*)\}/u', function($m){
            $inner = $m[1];
            $safeInner = htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeInner = strtr($safeInner, ['_' => '&#95;', '^' => '&#94;', '\\' => '' ]);
            return '<span class="math-text">'.$safeInner.'</span>';
        }, $tex);

        for ($pass=0; $pass<8; $pass++) {
            $pos = strpos($tex, '\frac');
            if ($pos === false) break;
            $i = $pos + 5;
            $num = $this->extractBraced($tex, $i);
            $den = $this->extractBraced($tex, $i);
            if ($num === '' || $den === '') break;
            $numH = $this->renderLatex($num);
            $denH = $this->renderLatex($den);
            $html = '<span style="display:inline-block;vertical-align:middle;text-align:center;">'
                  . '<span style="display:block;border-bottom:1px solid currentColor;">'.$numH.'</span>'
                  . '<span style="display:block;">'.$denH.'</span>'
                  . '</span>';
            $tex = substr($tex, 0, $pos) . $html . substr($tex, $i);
        }

        $tex = preg_replace_callback('/(_|\^)\s*\{([^{}]*)\}/u', function($m){
            $tag = ($m[1] === '_') ? 'sub' : 'sup';
            return '<'.$tag.'>'.$this->renderLatex($m[2]).'</'.$tag.'>';
        }, $tex);
        $tex = preg_replace('/_\s*([A-Za-z0-9])/', '<sub>$1</sub>', $tex);
        $tex = preg_replace('/\^\s*([A-Za-z0-9])/', '<sup>$1</sup>', $tex);

        return preg_replace('/\s+/u', ' ', $tex);
    }

    private function extractBraced(string $src, int &$i): string
    {
        $n = strlen($src);
        while ($i < $n && $src[$i] !== '{') $i++;
        if ($i >= $n || $src[$i] !== '{') return '';
        $i++; $depth = 1; $out = '';
        while ($i < $n && $depth > 0) {
            $ch = $src[$i];
            if ($ch === '{') { $depth++; $out .= $ch; }
            elseif ($ch === '}') { $depth--; if ($depth>0) $out .= $ch; }
            else { $out .= $ch; }
            $i++;
        }
        return $out;
    }
}
