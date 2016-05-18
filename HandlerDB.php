<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of handlerDB
 *
 * @author Harry
 */
class HandlerDB extends SQLite3{
    
    function __construct($filename)
    {
        $this->open($filename);
    }
    
    function getATTrkPts() {
        $stmt1 = $this->prepare("SELECT * FROM maintrails WHERE trail='AT';");
        $ATtrkPtsResult = $stmt1->execute();
        $ATtrkPts = [];
        while($row = $ATtrkPtsResult->fetchArray(SQLITE3_ASSOC)) {
            $ATtrkPts[] = $row;
        }
        return $ATtrkPts;
    }
    
    function getNonATTrkPts() {
        $stmt1 = $this->prepare("SELECT * FROM maintrails WHERE NOT(trail='AT');");
        $ATtrkPtsResult = $stmt1->execute();
        $ATtrkPts = [];
        while($row = $ATtrkPtsResult->fetchArray(SQLITE3_ASSOC)) {
            $ATtrkPts[] = $row;
        }
        return $ATtrkPts;
    }
    
    function createWptTable() {
        $this->exec("CREATE TABLE atwpt ("
                . "name TEXT NOT NULL PRIMARY KEY, "
                . "type TEXT NOT NULL, "
                . "desc TEXT NOT NULL, "
                . "lat REAL NOT NULL, "
                . "lon REAL NOT NULL, "
                . "ele INT NOT NULL, "
                . "trail TEXT NOT NULL, "
                . "letter TEXT NOT NULL, "
                . "mile REAL NOT NULL);");
    }
    
    function dropWptTable() {
        $this->exec("DROP TABLE atwpt;");
    }
    
    function addWpt($waypoint) {
        $existsStmt = $this->prepare("SELECT EXISTS(SELECT * FROM atwpt WHERE name=':name');");
        $existsStmt->bindParam(':name', $waypoint['name'], SQLITE3_TEXT);
        $existsResult = $existsStmt->execute();
        $hi = $existsResult->fetchArray();
        echo "\n hi:" . $hi[0];
        if ($hi[0]) {
            return 0;
        }
        $stmt = $this->prepare("INSERT INTO atwpt VALUES (:name, :type, :desc, "
                . ":lat, :lon, :ele, :trail, :letter, :mile);");
        $stmt->bindParam(':name', $waypoint['name'], SQLITE3_TEXT);
        $stmt->bindParam(':type', $waypoint['type'], SQLITE3_TEXT);
        $stmt->bindParam(':desc', $waypoint['desc'], SQLITE3_TEXT);
        $stmt->bindParam(':lat', $waypoint['lat'], SQLITE3_FLOAT);
        $stmt->bindParam(':lon', $waypoint['lon'], SQLITE3_FLOAT);
        $stmt->bindParam(':ele', $waypoint['ele'], SQLITE3_INTEGER);
        $stmt->bindParam(':trail', $waypoint['trail'], SQLITE3_TEXT);
        $stmt->bindParam(':letter', $waypoint['letter'], SQLITE3_TEXT);
        $stmt->bindParam(':mile', $waypoint['mile'], SQLITE3_FLOAT);
        $stmt->execute();
        return 1;
    }
}
