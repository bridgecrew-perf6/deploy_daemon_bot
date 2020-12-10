<?php

require "vendor/autoload.php";
use Daemon\Sdk\Init;
$a = new Init($argv);

die();


$fetch = "git fetch 2>&1";
$diff = "git diff origin/main..main --name-only 2>&1";

$output = [];
$filesMap = [];
echo sprintf("\n[%s] start monitoring", date('H:i d.m.Y'));

while(true){
    $configBody = file_get_contents("deploy-config.json");
    $config = json_decode($configBody, true);
    if(is_null($config)){
        sleep(10);
    }
    exec($fetch, $output);
    if (count($output) > 0) {
        $output = [];
        exec($diff, $output);
        $files = $output;
        foreach ($files as $file) {
            $explodeFilePath = explode("/", $file);
            $countExplode = count($explodeFilePath);
            if ($countExplode > 0) {
                $fileName = $explodeFilePath[$countExplode - 1];
                unset($explodeFilePath[$countExplode - 1]);

                $filePath = "./" . implode('/', $explodeFilePath);
                $filesMap[$filePath][] = $fileName;
            } else {
                $filePath = "./";
                $filesMap[$filePath][] = $file;
            }
        }
        $filesMap["./"][] = 1;
        for ($i=0; $i<3; $i++) {
            foreach ($filesMap as $path=>$files) {
                if (in_array($path, array_keys($config['dirs']))) {
                    if ($i === 0) {
                        foreach ($config['dirs'][$path]['before_push'] as $command) {
                            exec($command);
                        }
                    } elseif ($i === 1) {
                        exec("git pull");
                    } else {
                        foreach ($config['dirs'][$path]['after_push'] as $command) {
                            exec($command);
                        }
                    }
                }
            }
        }
        echo sprintf("\n[%s] New pull was been successful handle", date('H:i d.m.Y'));
    }
    sleep(5);
}
?>