<html>
    <head>
        <TITLE>GitHub PHP Deployer</TITLE>
    </head>
    <body>
        <h1>GitHub Revision Deployer for PHP</h1>
        <?php
        //query string parameters
        $revision = $_GET["revision"];
        $project = $_GET["project"];
        $branch = $_GET["branch"];
        $owner = $_GET["owner"];
        $name = $_GET["name"];

        if ($owner != null && $project != null && $branch != null) {
            //Path to download and install all revisions
            $rootpath = "./" . str_replace(".", "_", $owner . "/" . $project . "/" . $branch . "/");
            //Path to deploy the revision
            $dplypath = "./" . str_replace(".", "_", $project . "/" . $branch . "/");
            //Query string to be re-used by deployer
            $querystr = "?project=" . $project . "&branch=" . $branch . "&owner=" . $owner;

            //Push repository from github.com
            $filedata = pushRepository($owner, $project, $branch);
            //Save repository into local disk
            saveRepository($rootpath, $filedata);
            //Deploy repository in server
            deployRepository($rootpath);

            //If deploy options are specified deploy it
            if ($revision != null && $name != null) {
                deployRevision($rootpath, $dplypath, $name, $revision);
            }

            //Print repositories for referencing
            printRepositories($rootpath, $querystr, $name);
            //Print deployed revisions for referencing
            printDeployedRevisions($dplypath);
        } else {
            echo "Specify parameters: 'owner', 'project', 'branch', 'name' from github.com repository";
        }

        function pushRepository($owner, $project, $origin) {
            //The root GitHub URL
            $giturl = 'https://nodeload.github.com';
            $giturl = $giturl . '/' . $owner;
            $giturl = $giturl . '/' . $project;
            $giturl = $giturl . '/zipball/';
            $giturl = $giturl . '/' . $origin;

            //Mount the url to connect with github and download the contents
            $rhandle = fopen($giturl, 'r');
            $filedata = stream_get_contents($rhandle);
            fclose($rhandle);

            return $filedata;
        }

        function saveRepository($rootpath, $filedata) {
            //Create file if doesn't exists
            if (!file_exists($rootpath)) {
                mkdir($rootpath, 0777, true);
            }
            //Write downloaded contents into disk
            $lhandle = fopen($rootpath . 'latestimport.zip', 'wb');
            fwrite($lhandle, $filedata);
            fclose($lhandle);
        }

        function deployRepository($rootpath) {
            //Unzip contents
            $zip = new ZipArchive;
            $res = $zip->open($rootpath . 'latestimport.zip');
            if ($res === TRUE) {
                //Extract contents of downloaded zip into correct folder
                $zip->extractTo($rootpath);
                $zip->close();
            } else {
                echo 'Deploy Failed!';
            }
        }

        function printRepositories($rootpath, $querystr, $name) {
            if (file_exists($rootpath)) {
                $files = scandir($rootpath);
                echo "\n<table>";
                echo "\n<p>Each row represents a imported revision, you can access and deploy the imported revisions</p>";
                foreach ($files as $filename) {
                    if (
                            strcasecmp($filename, "latestimport.zip") &&
                            strcasecmp($filename, "..") &&
                            strcasecmp($filename, ".")
                    ) {
                        echo "\n<tr>";
                        echo "\n  <td>- Environment and Revision for Deployment: <a href=\"" . $rootpath . $filename . "\">" . $filename . "</a></td>";
                        if ($name != null) {
                            echo "\n  <td><a href=\"" . $querystr . "&name=" . $name . "&revision=" . $filename . "\">Deploy!</a></td>";
                        } else {
                            echo "\n  <td>Missing 'name' for deployment</td>";
                        }
                        echo "\n</tr>";
                    }
                }
                echo "</table>";
            }
        }

        function deployRevision($rootpath, $dplypath, $deployname, $deployrevision) {
            if (!file_exists($dplypath)) {
                mkdir($dplypath, 0777, true);
            }

            $dplypath = $dplypath . $deployname;
            $sourcepath = $rootpath . $deployrevision;
            copy_directory($sourcepath, $dplypath);
        }

        function printDeployedRevisions($dplypath) {
            if (file_exists($dplypath)) {
                $files = scandir($dplypath);
                echo "\n<table>";
                echo "\n<p>Each row represents a deployed revision, click to gain access the deployed environment</p>";
                foreach ($files as $filename) {
                    if (strcasecmp($filename, "..") && strcasecmp($filename, ".")) {
                        echo "\n<tr>";
                        echo "\n  <td>- Currently Deployed Revision: <a href=\"" . $dplypath . $filename . "\">" . $filename . "</a></td>";
                        echo "\n</tr>";
                    }
                }
                echo "</table>";
            }
        }

        function copy_directory($source, $destination) {
            if (is_dir($source)) {
                @mkdir($destination);
                $directory = dir($source);
                while (FALSE !== ( $readdirectory = $directory->read() )) {
                    if ($readdirectory == '.' || $readdirectory == '..') {
                        continue;
                    }
                    $PathDir = $source . '/' . $readdirectory;
                    if (is_dir($PathDir)) {
                        copy_directory($PathDir, $destination . '/' . $readdirectory);
                        continue;
                    }
                    copy($PathDir, $destination . '/' . $readdirectory);
                }

                $directory->close();
            } else {
                copy($source, $destination);
            }
        }
        ?>
        <h3>Created by Rafael Karst <a href="mailto:rafaelkarst@gmail.com">mail</a> for <a href="https://github.com/">github.com</a></h3>
    </body>
</html>