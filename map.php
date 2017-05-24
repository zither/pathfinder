<?php

class Point {
    public $name;
    public $index;
    public $id;
    public $x;
    public $y;
    public $cmd;
    public $g;
    public $h;
    public $f;
    public $parent;
    public $isClosed = false;

    public function __construct($x, $y, $cmd = null, $parent = null, $g = 0, $h = 0) 
    {
        $this->x = $x;
        $this->y = $y;
        $this->cmd = $cmd;
        $this->g = $g;
        $this->h = $h;
        $this->parent = $parent;
    }

    public function id()
    {
        if (!empty($this->id)) {
            return $this->id;
        }
        return $this->id = sprintf("x%dy%d", $this->x, $this->y);
    }

    public function f()
    {
        return $this->g + $this->h;
    }

    public function setParent(Point $parent)
    {
        $this->parent = $parent;
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

class HeadList 
{
    protected $data = [];
    protected $closed = [];
    protected $head = [];

    public function insert(Point $point) 
    {
        if (!isset($this->data[$point->id()])) {
            $this->data[$point->id()] = $point;
            $this->head[] = $point;
            $point->setIndex(count($this->head) - 1);
            $this->upSortHeadFrom($point->index);
        }
    }

    public function remove() 
    {
        $first = $this->head[0];
        $lastIndex = count($this->head) - 1; 
        $this->head[0] = $this->head[$lastIndex];
        $this->head[0]->setIndex(0);

        array_pop($this->head);
        $this->closed[] = $first->id();

        $this->downSortHead();
        return $first;
    }

    protected function swap($indexA, $indexB)
    {
        $tmp = $this->head[$indexA];
        $this->head[$indexA] = $this->head[$indexB];
        $this->head[$indexA]->setIndex($indexA);
        $this->head[$indexB] = $tmp;
        $this->head[$indexB]->setIndex($indexB);
    }

    public function isEmpty()
    {
        return empty($this->head);
    }

    public function isClosed(Point $point)
    {
        if (!isset($this->data[$point->id()])) {
            return false;
        }

        return in_array($point->id(), $this->closed);
    }

    public function addClosed(Point $point) 
    {
        if (!isset($this->data[$point->id()])) {
            $this->data[$point->id()] = $point;
        }
        if (!in_array($point->id(), $this->closed)) {
            $this->closed[] = $point->id();
        }
    }

    public function isOpen(Point $point) 
    {
        return isset($this->data[$point->id()]) && !in_array($point->id(), $this->closed);
    }

    public function upSortHeadFrom($i) 
    {
        while ($i !== 0) {
            $parentIndex = ($i % 2) ? ($i - 1) / 2 : ($i - 2) / 2;
            $parentKey = $this->head[$parentIndex]->id();
            if ($this->head[$parentIndex]->f() > $this->head[$i]->f()) {
                $this->swap($parentIndex, $i);
                $i = $parentIndex;
            } else {
                break;
            }
        }
    }

    public function downSortHead()
    {
        $i = 0;
        $len = count($this->head);
        while ($i < $len - 1) {
            $left = 2 * $i + 1;
            $right = 2 * $i + 2;  

            if (!isset($this->head[$left])) {
                break;
            }

            if (!isset($this->head[$right])) {
                $this->swap($i, $left);
                break;
            }

            if ($this->head[$left]->f() < $this->head[$right]->f()) {
                $this->swap($i, $left);
                $i = $left;
            } else {
                $this->swap($i, $right);
                $i = $right;
            }
        }
    }

    public function update(Point $point)
    {
        if ($this->data[$point->id()]->f() > $point->f()) {
            $this->data[$point->id()]->g = $point->g;
            $this->data[$point->id()]->cmd = $point->cmd;
            $this->data[$point->id()]->setParent($point->parent);
            $this->upSortHeadFrom($this->data[$point->id()]->index);
        }
    }

    public function getPoint($id)
    {
        return isset($this->data[$id]) ? $this->data[$id] : false;
    }
}

class Map 
{
    protected $data = [];
    protected $costs = [];
    protected $openList;
    protected $cmds = [];
    protected $minCost;
    protected $reverseExits = [
        'n' => 's',
        's' => 'n',
        'w' => 'e',
        'e' => 'w',
        'nw' => 'se',
        'ne' => 'sw',
        'sw' => 'ne',
        'se' => 'nw',
    ];
    
    public function __construct()
    {
        $lines = file("laenor.map", FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $num => $line) {
            $this->data[] = str_split($line);
        }
        $this->costs = json_decode(file_get_contents("costs.json"), true);
        $this->calMinCost();
        $this->openList = new HeadList();
    }

    protected function calMinCost()
    {
        //$values = array_values($this->costs);
        $this->minCost = 9;
    }

    public function setMinCost($cost)
    {
        $this->minCost = $cost;
        return $this;
    }

    public function findPath(Point $a, Point $b) 
    {
        $current = $a;
        while ($current !== null) {
            if (!$this->openList->isOpen($a)) {
                $this->openList->addClosed($a);
            }
            $this->getRelativePoints($current, $b);

            if ($this->openList->isOpen($b)) {
                break;
            }

            $current = null;
            if (!$this->openList->isEmpty()) {
                $current = $this->openList->remove();
            }
        }

        if ($this->openList->isOpen($b)) {
            $endPoint = $this->openList->getPoint($b->id());
            array_unshift($this->cmds, $endPoint->cmd);
            $parent = $endPoint->parent;
            while ($parent) {
                if ($parent->cmd) {
                    array_unshift($this->cmds, $parent->cmd);
                }
                $parent = isset($parent->parent) ? $parent->parent : null;
            }

            $reverseExits = $this->reverseExits;
            $reverseCmds = array_map(function($cmd) use ($reverseExits) {
                return $reverseExits[$cmd]; 
            }, array_reverse($this->cmds));

            echo sprintf("%s -> %s\n", $a->name, $b->name);
            echo json_encode($this->parsePath($this->cmds), JSON_PRETTY_PRINT), "\n";
            echo sprintf("%s -> %s\n", $b->name, $a->name);
            echo json_encode($this->parsePath($reverseCmds), JSON_PRETTY_PRINT), "\n";
        } else {
            echo "Path not found.\n";
        }
    }

    protected function parsePath($cmds)
    {
        $pathArray = [];
        $cmdsArray = array_chunk($cmds, 100);
        foreach ($cmdsArray as $commands) {
            $prevcmd = null;
            $prevnum = 1;
            $path = [];
            foreach ($commands as $index => $cmd) {
                if ($prevcmd == $cmd && $prevnum < 20) {
                    $prevnum++;
                } else {
                    if ($prevcmd) {
                        $path[] = [$prevcmd =>$prevnum];
                    } 
                    $prevcmd = $cmd;
                    $prevnum = 1;
                }
            }
            $path[] = [$prevcmd => $prevnum];

            $result = [];
            foreach ($path as $c) {
                $key = key($c);
                if ($c[$key] > 1) {
                    $result[] = sprintf("%d %s", $c[$key], $key);
                } else {
                    $result[] = $key;
                }
            }
            $pathArray[] = implode($result, ";");
        }

        return $pathArray;
    }

    protected function getRelativePoints(Point $a, Point $b)
    {
        $tmp = [];
        $tmp[] = new Point($a->x, $a->y - 1, "n", $a);
        $tmp[] = new Point($a->x, $a->y + 1, "s", $a);
        $tmp[] = new Point($a->x - 1, $a->y, "w", $a);
        $tmp[] = new Point($a->x + 1, $a->y, "e", $a);
        $tmp[] = new Point($a->x + 1, $a->y - 1, "ne", $a);
        $tmp[] = new Point($a->x - 1, $a->y - 1, "nw", $a);
        $tmp[] = new Point($a->x + 1, $a->y + 1, "se", $a);
        $tmp[] = new Point($a->x - 1, $a->y + 1, "sw", $a);

        foreach ($tmp as $point) {
            if (isset($this->data[$point->y][$point->x])) {
                $char = $this->data[$point->y][$point->x];

                if (!isset($this->costs[$char]) || $this->openList->isClosed($point)) {
                    continue;
                }

                $point->g = $a->g + $this->costs[$char];
                $point->h = sqrt(pow($point->x - $b->x, 2) + pow($point->y - $b->y, 2)) * $this->minCost;
                if ($this->openList->isOpen($point)) {
                    $this->openList->update($point);
                } else {
                    $this->openList->insert($point);
                }
            }
        }
    }
}


if (count($_SERVER['argv']) < 3) {
    echo "Usage: php map.php <loc1> <loc2>\n";
    exit;
}

$from = $_SERVER['argv'][1];
$to = $_SERVER['argv'][2];
$locations = json_decode(file_get_contents("location.json"), true);
foreach ($locations['laenor'] as $name => $loc) {
    if (is_object($from) && is_object($to)) {
        break;
    }

    if ($name == $from) {
        $from = new Point($loc['x'] - 1, $loc['y'] - 1);
        $from->setName($name);
    }

    if ($name == $to) {
        $to = new Point($loc['x'] - 1, $loc['y'] - 1);
        $to->setName($name);
    } 
}

if (is_string($from)) {
    printf("Can't find %s\n", $from);
    exit;
}
if (is_string($to)) {
    printf("Can't find %s\n", $to);
    exit;
}

$map = new Map();
$t1 = microtime(true);
$map->findPath($from, $to);
$t2 = microtime(true);
echo ($t2 - $t1) * 1000, "ms\n";
