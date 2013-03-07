<?php

# Copyright (c) 2013 Michele Bini

# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

require 'LAB.js';

class Lib {
    public $nym;
    public $dep;
    public $usr;
    static public $libs = [];
    public function __construct($name, $deps = []) {
        $this->nym = $name;
        $this->dep = [];
        $this->requires($deps);
        self::$libs[$name] = $this;
    }
    public function requires($x) {
        if (is_array($x)) {
            foreach ($x as $y) $this->requires($y);
            return $this;
        }
        if (isset($x->nym)) {
            $dep = $x;
            $nym = $x->nym;
        } else {
            $dep = self::$libs[$x];
            $nym = $x;
        }
        $this->dep[$nym] = $dep;
        return $this;
    }
    public function requiredby($x) {
    }
}

class Src extends Lib {
    public $src;
    public function __construct($name, $src, $deps = []) {
        parent::__construct($name, $deps);
        $this->src = $src;
    }
}

class Snip extends Lib {
    public $snip;
    public function __construct($name, $snip, $deps = []) {
        parent::__construct($name, $deps);
        $this->snip = $snip;
    }
}

class LoadChain {
    private $chain;
    private $levels;
    public function __construct() {
        $this->chain = [];
        $this->levels = [];
    }
    public function add($x) {
        $n = $x->nym;
        $dep = $x->dep;
        $l = 0;
        if (isset($this->levels[$n])) return $this->levels[$n];
        foreach ($dep as $a) {
            $r = $this->add($a);
            if ($r >= $l) $l = $r+1;
        }
        if ($l >= count($this->chain)) $this->chain[$l] = [ 0 => [], 1 => [] ];
        # print "Adding $n at level $l\n";
        $this->chain[$l][($x instanceof Src) ? 0 : 1][$n] = $x;
        return $this->levels[$n] = $l;
    }
    public function out() {
        $w = false;
        foreach ($this->chain as $b) {
            if ($w) {
                if ($b[1]) {
                    print "\n.wait(function(){\n";
                    foreach ($b[1] as $x) {
                        $x = $x->snip;
                        print "(function(){$x})();\n";
                    }
                    print "})";
                } else {
                    print ".wait()";
                }
            } else {
                foreach ($b[1] as $x) {
                    $x = $x->snip;
                    print "(function(){$x})();\n";
                }
                print '$LAB';
            }
            foreach ($b[0] as $x) {
                print ".script('" . $x->src . "')";
            }
            $w = true;
        }
        print(";");
    }
}

require 'jsconfig.php';

$chain = new LoadChain;
foreach (explode(':', $q) as $_GET["q"]) $chain->add(Lib::$libs[$l]);
$chain->out();
