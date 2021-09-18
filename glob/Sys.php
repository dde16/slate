<?php

abstract class Sys {
    public static function getMemorySize(): string {
        return explode("\n", shell_exec("cat /proc/meminfo | sed 's/ //g' | head -n 1 | awk '{split($0, items, \":\"); print items[2] / 1024 / 1024}'"))[0];
    }
    
    public static function getProcessorName(): string {
        return explode("\n", shell_exec("lscpu | sed -nr '/Model name/ s/.*:\s*(.*) @ .*/\\1/p'"))[0];
    }
    
    public static function getNetworkTraffic(): array|null {
        if(file_exists("/sys/class/net/enp1s0/statistics/rx_bytes")) {
            if(file_exists("/sys/class/net/enp1s0/statistics/tx_bytes")) {
                if($rx = file_get_contents("/sys/class/net/enp1s0/statistics/rx_bytes")) {
                    if($tx = file_get_contents("/sys/class/net/enp1s0/statistics/tx_bytes")) {
                        return ["rx" => intval($rx), "tx" => intval($tx)];
                    }
                }
            }
        }

        return null;
    }
    
    public static function getMemoryUsage(): int|float {
        $free = shell_exec("free");
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memoryUsage = $mem[2]/$mem[1];
    
        return $memoryUsage;
    }
    
    public static function getProcessorUsage(): int|float {
        $load = sys_getloadavg();
        return $load[0];
    }
    
    public static function getThermals(): array {
        $output = array();
    
        foreach(scandir("/sys/class/thermal/") as $path) {
            if($path != "." && $path != "..") {
                if(substr($path, 0, strlen("thermal_zone")) == "thermal_zone") {
                    if($temp_str = explode("\n", file_get_contents("/sys/class/thermal/".$path."/temp"))[0]) {
                        if($temp = intval($temp_str)) {
                            $output[$path] = $temp / 1000;
                        }
                    }
                }
            }
        }
    
        return $output;
    }
    
    public static function getPhysicalDisks(): array {
        $arr = array();
    
        $output = shell_exec("lsblk -d -io KNAME,TYPE,SIZE,MODEL -b -r");
    
        $lines = explode("\n", $output);
        unset($lines[count($lines)-1]);
    
        if($columns = explode(" ", $lines[0])) {
            unset($lines[0]);
    
            foreach($lines as $index => $row) {
                $items = explode(" ", $row);
    
                $arr_row = array();
    
                foreach($items as $_index => $value) {
                    $arr_row[$columns[$_index]] = str_replace("\\x20", " ", $value);
    
                    if($columns[$_index] == "KNAME") {
                        if(file_exists("/dev/".$value)) {
                            if($temp_str = shell_exec("sudo hddtemp --numeric /dev/".$value)) {
                                if($temp_int = intval($temp_str)) {
                                    $arr_row["TEMP"] = $temp_int;
                                }
                            }
                        }
                    }
                }
    
                $arr[] = $arr_row;
            }
        }
    
        return $arr;
    }
    
    public static function getRunningDaemons(): array {
         $ps = explode("\n", shell_exec("ps axo pid,pcpu,pmem,ppid,pgrp,comm | awk '$4==1' | awk '$1==$5' | awk '{print $1\",\"$2\",\"$3\",\"$6}'"));
    
         unset($ps[count($ps)-1]);
    
         return array_map(function($value) {
             $items = explode(",", $value);
    
             return array(
                 "pid" => $items[0],
                 "pcpu" => $items[1],
                 "pmem" => $items[2],
                 "comm" => $items[3]
             );
         }, $ps);
    }
    
    public static function isDaemonRunning($name): bool {
        $ps = explode("\n", exec("ps axo pid,pcpu,pmem,ppid,pgrp,comm | awk '$4==1' | awk '$1==$5' | awk '$6==\"$name\"' | awk '{print \"true\"}'"))[0];
    
        return ($ps == "true");
    }
}

?>