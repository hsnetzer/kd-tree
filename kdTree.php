<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author Harry
 */
class kdTree {
    
    private $kdTree;
    
    function __construct($mode, $array) {
        if ($mode == "generate") { 
            $this->kdTree = $this->makekd($array['array']);
        }
        else if ($mode == "load") {
            $this->kdTree = unserialize(file_get_contents($array["file"]));
        }
    }
    
    function makekd($ptList, $depth = 0) {
        if (!$ptList) { return null; }
        if (count($ptList) == 1) { return $ptList[0]; }

        if ($depth % 2) {
            usort($ptList, array('kdTree', 'cmpLon'));
            $d = 'lon';
        } else {
            usort($ptList, array('kdTree', 'cmpLat'));
            $d = 'lat';
        }
        
        $medianPt = $ptList[count($ptList) / 2];
        $leftPts = [];
        $middlePts = [];
        $rightPts = [];
        foreach ($ptList as $pt) {
            if ($pt[$d] < $medianPt[$d]) {
                $leftPts[] = $pt;
            } else if ($pt[$d] == $medianPt[$d]) {
                $middlePts[] = $pt;
            } else {
                $rightPts[] = $pt;
            }
        }
        if (count($leftPts) < count($rightPts)) {
            $leftPts = array_merge($leftPts, $middlePts);
        } else {
            $rightPts = array_merge($middlePts, $rightPts);
        }
        $axis = $rightPts[0][$d];
        
        $leftBranch = $this->makekd($leftPts, $depth + 1);
        $rightBranch = $this->makekd($rightPts, $depth + 1);
        return new Node($leftBranch, $rightBranch, $axis);
    }
    
    function kdDump($filename) {
        file_put_contents($filename, serialize($this->kdTree));
    }
    
    function kdNN($testPt) {
        return $this->kdNN2($testPt, $this->kdTree);
    }
    
    function kdNN2($testPt, $node, $depth = 0) {
        if (!$node) { return null; }
        else if (gettype($node) == "array") { return $node; }
        
        $d = ($depth % 2) ? 'lon' : 'lat';
        
        if ($testPt[$d] < $node->getAxis()) {
            $thisBranch = $node->getLeft();
            $otherBranch = $node->getRight();
        } else {
            $thisBranch = $node->getRight();
            $otherBranch = $node->getLeft();
        }
        
        $thisBranchNN = $this->kdNN2($testPt, $thisBranch, $depth + 1);
        if ($thisBranchNN) {
            $NNDist = self::haversine($thisBranchNN, $testPt);
            
            // if distance is less than 50 feet, return
            if ($NNDist < 2.4e-6) {
                return $thisBranchNN;
            }
            
            // get distance from query point to last splitting axis
            $orthoDist = self::orthoDist($testPt, $node->getAxis(), $d, $NNDist);
            
            // if hypersphere does not intersect node's splitting axis
            if ($NNDist < $orthoDist) {
                return $thisBranchNN;
            } else {
                $otherBranchNN = $this->kdNN2($testPt, $otherBranch, $depth + 1);
                if ($NNDist < self::haversine($otherBranchNN, $testPt)) {
                    return $thisBranchNN;
                } else {
                    return $otherBranchNN;
                }
            }
        } else {
            return $this->kdNN2($testPt, $otherBranch, $depth + 1);
        }
    }
    
    /*
     * returns the absolute value, in feet, of either the latitudinal or 
     * longitudinal displacement between between two points given
     * by arrays containing degree values for keys 'lon' and 'lat'
     */
    static function orthoDist($p1, $coord, $d, $NNdist) {
        $dist = rad2deg($NNdist);
        
        if ($d == 'lon') {
            return haversine(['lat' => $p1['lat'] + $dist, 'lon' => $p1['lon']], ['lat' => $p1['lat'] + $dist, 'lon' => $coord]);
        } else if ($d == 'lat') {
            return abs(deg2rad($p1['lat']) - deg2rad($coord));
        }
    }

    /*
     * using haversine formula, returns the unitless displacement between two 
     * trackpoint arrays containing degree values for keys 'lat' and 'lon'
     */
    static function haversine($p1, $p2) {
        $phi1 = deg2rad($p1['lat']);
        $phi2 = deg2rad($p2['lat']);
        $deltaLambda = deg2rad($p2['lon'] - $p1['lon']);

        $a = sin(($phi2 - $phi1) / 2) * sin(($phi2 - $phi1) / 2) + cos($phi1) * 
                cos($phi2) * sin($deltaLambda / 2) * sin($deltaLambda / 2);
        return 2 * atan2(sqrt($a), sqrt(1 - $a));

    }
    
    /*
     * returns 0 if arguments $a and $b (waypt arrays) are on the same parallel, 
     * -1 if $a is south of $b, and 1 if $a is north of $b
     */
    static function cmpLat($a, $b)
    {
        if ($a['lat'] == $b['lat']) {
            return 0;
        }
        return ($a['lat'] < $b['lat']) ? -1 : 1;
    }

    /*
     * returns 0 if arguments $a and $b (waypt arrays) are on the same 
     * meridian, -1 if $a is west of $b, and 1 if $a is east of $b
     */
    static function cmpLon($a, $b)
    {
        if ($a['lon'] == $b['lon']) {
            return 0;
        }
        return ($a['lon'] < $b['lon']) ? -1 : 1;
    }
}

class Node {
    private $left;
    private $right;
    private $axis;
    
    public function __construct($left, $right, $axis) {
        $this->left = $left;
        $this->right = $right;
        $this->axis = $axis;
    }
    
    function getLeft() {
        return $this->left;
    }
    
    function getRight() {
        return $this->right;
    }
    
    function getAxis() {
        return $this->axis;
    }
}