<?php // GPS locations path test // Javier Rey // javier.rey.eu@gmail.com // April 2015, from a previous test developed March, 2013

$iDoc = new Doc();

class Doc
{
    // Properties:

    public static $locationsHistory;
	public static $locationsResult;
	public static $V_THRESHOLD; // Natural Car Speed Limit
	public static $A_THRESHOLD; // Natural Car Acceleration Limit
	public static $TIME_SCALE; // Time Scale

    // Constructor:

    /**
     * Function getlocationsHistory. Gets the input parameters and runs the script's logic.
     */
    function __construct()
    {
        self::$V_THRESHOLD = isset($_GET["v"]) ? floatval($_GET["v"]) : 0;
        self::$A_THRESHOLD = isset($_GET["a"]) ? floatval($_GET["a"]) : 0;
        self::$TIME_SCALE = isset($_GET["t"]) ? floatval($_GET["t"]) : 0;

        if (self::$V_THRESHOLD == 0) {self::$V_THRESHOLD = 3.65;} // Def Default
        if (self::$A_THRESHOLD == 0) {self::$A_THRESHOLD = 1.32;} // Def Default
        if (self::$TIME_SCALE == 0) {self::$TIME_SCALE = 15;} // Def Default

        if (self::$V_THRESHOLD == -1) {self::$V_THRESHOLD = 1e2;} // Huge, no filter
        if (self::$V_THRESHOLD == -2) {self::$V_THRESHOLD = 0;} // Stops full filter

        if (self::$A_THRESHOLD == -1) {self::$A_THRESHOLD = 1e2;} // Huge, no filter
        if (self::$A_THRESHOLD == -2) {self::$A_THRESHOLD = 0;} // Stops full filter

        self::setlocationsHistory();
        self::removeFalseLocations();
    }

    // Functions:

    /**
     * Function setlocationsHistory. Sets the locations list from CSV source and sorts it on timestamp values.
     */
    private static function setlocationsHistory()
    {
        $src = file_get_contents('assets/content/points.csv');
        self::$locationsHistory = self::csvArray($src);
        foreach (self::$locationsHistory as &$line) {
            foreach ($line as &$field) {
                $field = floatval($field);
            }
        }
        uksort(self::$locationsHistory, 'self::csvCompareOnTimestamp'); // echo "setlocationsHistory: ".print_r(self::$locationsHistory, true);
    }

    /**
     * Function removeFalseLocations. Removes unnatural locations based on speed and acceleration thresholds.
     */
	private static function removeFalseLocations()
    {
		$vlim = self::$V_THRESHOLD*1e-4; // Constant Adjust
		$alim = self::$A_THRESHOLD*1e-5; // Constant Adjust
		self::$locationsResult = array();
		for ($i = 0; $i < count(self::$locationsHistory); $i++) {
			$valid = true;
			if ($i > 0 && $i < count(self::$locationsHistory)-1) { // Always Plot First and Last Pos
				$dt = self::$locationsHistory[$i][2]-self::$locationsHistory[$i-1][2];
				$sx = self::$locationsHistory[$i][1]-self::$locationsHistory[$i-1][1]; // Longitude index 1
				$sy = self::$locationsHistory[$i][0]-self::$locationsHistory[$i-1][0]; // Latitude index 0
				$ds = sqrt($sx*$sx+$sy*$sy);
				$v = $ds/$dt;
				if (abs($v) > $vlim) {
					$valid = false;
				} else if ($i > 1) {
					$dt0 = self::$locationsHistory[$i-1][2]-self::$locationsHistory[$i-2][2];
					$sx0 = self::$locationsHistory[$i-1][1]-self::$locationsHistory[$i-2][1];
					$sy0 = self::$locationsHistory[$i-1][0]-self::$locationsHistory[$i-2][0];
					$ds0 = sqrt($sx0*$sx0+$sy0*$sy0);
					$v0 = $ds0/$dt0;
					$a = ($v-$v0)/$dt;
					if (abs($a) > $alim) {
						$valid = false;
					}
				}
			}
			if ($valid) {self::$locationsResult[] = self::$locationsHistory[$i];}
		} // echo "removeFalseLocations ".$vlim.", ".$alim.": ".count(self::$locationsResult)."/".count(self::$locationsHistory);
	}

    /**
     * Function csvArray: Converts a CSV string into an array of arrays.
     * Checks each field for closure and separator in content.
     */
    private static function csvArray($csv, $fldsep = null, $linsep = null, $fldenc = null)
    {
        if (!$fldsep) {$fldsep = ",";}
        if (!$linsep) {$linsep = "\n";}
        if (!$fldenc) {$fldenc = '"';}
        $rx = $fldenc; if ($rx == '"' || $rx == "'") {$rx = "\\".$rx;}
        $rx = '/'.$fldsep.'(?=(?:[^'.$rx.']*'.$rx.'[^'.$rx.']*'.$rx.')*(?![^'.$rx.']*'.$rx.'))/';
        $arr = array(); $csv = explode($linsep, $csv);
        for ($i = 0; $i < count($csv); $i++) {
            $line = trim($csv[$i]);
            if (!$line) {continue;}
            $line = preg_split($rx, $line);
            for ($j = 0; $j < count($line); $j++) {
                $l = strlen($line[$j]);
                if (strpos($line[$j], $fldenc) === 0 && strrpos($line[$j], $fldenc) === $l-1) {
                    $line[$j] = substr($line[$j], 1, $l-2);
                }
            }
            $arr[] = $line;
        }
        return $arr;
    }

    /**
     * Function csvCompareOnTimestamp: Compare callback for locations list sorting.
     */
    private static function csvCompareOnTimestamp($a, $b)
    {
        $a = $a[2]; $b = $b[2]; // Index of timestamp column.
        return ($a < $b) ? 1 : (($a > $b) ? -1 : 0);
    }

    /**
     * Function locationsHistoryToString. Converts locations array in a convenient string to be used later on the client side.
     */
    public static function locationsHistoryToString($arr)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $arr[$i] = implode(",", $arr[$i]);
        }
        $arr = implode(";", $arr);
        return $arr;
    }
}
?>
<html>
<head>
<style type="text/css">
body {background-color:#eeeeee;}
input,select,textarea,pre,span,a,p,li,td,div,body {font-family:Verdana,Arial,Helvetica; font-size:11px; text-decoration:none; color:#333366;}
#panelDiv {
	width: 740px; height: 720px;
	border: solid 1px #cccccc;
}
</style>
<script type="text/javascript">
init();
function init() {
	SRC_LOCATIONS_RESULT = '<?php echo Doc::locationsHistoryToString(Doc::$locationsResult); ?>';
    window.preloadImg = new Image();
    preloadImg.src = 'assets/img/cleaned.png';
	onload = start;
}
function start() { // alert("locationsHistory: "+locationsHistory);
    if (!preloadImg.complete) {setTimeout(start, 100); return;} // wait for map to be loaded.
	setLocationsHistory();
	setCanvas();
    beginPlotlocations();
}
function getPos(o){
 var arr = []; arr[0] = 0; arr[1] = 0;
 while (o) {arr[0] += o.offsetLeft; arr[1] += o.offsetTop; o = o.offsetParent;}
 return arr;
}
function setLocationsHistory() {
	locationsHistory = SRC_LOCATIONS_RESULT.split(";");
	for (var i = 0; i < locationsHistory.length; i++) {
		locationsHistory[i] = locationsHistory[i].split(",");
		for (var j = 0; j < locationsHistory[i].length; j++) {
			locationsHistory[i][j] = parseFloat(locationsHistory[i][j]);
		}
	}
}
function setCanvas() {
	panelDiv = document.getElementById("panelDiv");
	cornerLeft = -0.165; cornerRight = -0.110;
	cornerTop = 51.540; cornerBottom = 51.480;
	var p = getPos(panelDiv);
	panelLeft = parseInt(p[0]||panelDiv.x||panelDiv.offsetLeft||(panelDiv.style && (panelDiv.style.pixelLeft||panelDiv.style.left)),10)||0;
	panelTop = parseInt(p[1]||panelDiv.y||panelDiv.offsetTop||(panelDiv.style && (panelDiv.style.pixelTop||panelDiv.style.top)),10)||0;
	panelWidth = parseInt(panelDiv.offsetWidth||panelDiv.clientWidth||(panelDiv.style && (panelDiv.style.pixelWidth||panelDiv.style.width)));
	panelHeight = parseInt(panelDiv.offsetHeight||panelDiv.clientHeight||(panelDiv.style && (panelDiv.style.pixelHeight||panelDiv.style.height)));
	scaleW = panelWidth/(cornerRight-cornerLeft); scaleH = panelHeight/(cornerBottom-cornerTop);
	offsetX = 40; offsetY = -154; offsetW = .87; offsetH = 1.55;
	zeroTime = locationsHistory[0][2]-5;
    scaleTime = <?php echo Doc::$TIME_SCALE; ?>;
	panelDiv.style.backgroundImage = "url('"+preloadImg.src+"')";
}
function beginPlotlocations() {
    var img = document.createElement("img");
    img.src = "assets/img/locRed.png";
    img.id = "initImg";
    img.style.position = "absolute";
    img.style.zIndex = 1;
    img.style.left = (offsetX+panelLeft+offsetW*scaleW*(locationsHistory[0][1]-cornerLeft))+"px"; // Longitude index 1
    img.style.top = (offsetY+panelTop+offsetH*scaleH*(locationsHistory[0][0]-cornerTop))+"px"; // Latitude index 0
    panelDiv.appendChild(img);
    var steps = 100, timeout = 5000; // Def
    window.blink = 0;
    for (var i = 0; i < steps; i++) {
        setTimeout(function() {
            img.style.visibility = !((window.blink++)%2) ? 'visible' : 'hidden';
        }, i*5*timeout/steps);
    }
    setTimeout(plotLocations, timeout); // Def
}
function plotLocations() {
    window.imgs = [];
    for (var i = 0; i < locationsHistory.length; i++) {
        var img = document.createElement("img");
        img.src = "assets/img/locOrange.png";
        img.id = "loc"+i;
        img.style.position = "absolute";
        img.style.zIndex = 10*i+1;
        img.style.left = (offsetX+panelLeft+offsetW*scaleW*(locationsHistory[i][1]-cornerLeft))+"px"; // Longitude index 1
        img.style.top = (offsetY+panelTop+offsetH*scaleH*(locationsHistory[i][0]-cornerTop))+"px"; // Latitude index 0
        imgs.push(img);
        setTimeout('plotLocation('+i+');', (locationsHistory[i][2]-zeroTime)*scaleTime);
    }
}
function plotLocation(idx) {
	if (idx > 0) {imgs[idx-1].src = "assets/img/locBlue.png";}
	panelDiv.appendChild(imgs[idx]);
}
function delLocation(idx) {
	panelDiv.removeChild(imgs[idx]);
}
</script>
</head>
<body>
GPS locations path test.
Input Values: <?php echo "v=".Doc::$V_THRESHOLD.", a=".Doc::$A_THRESHOLD.", t=".Doc::$TIME_SCALE; ?>.
&nbsp;Output Points: <?php echo count(Doc::$locationsResult)."/".count(Doc::$locationsHistory); ?>
<div id="panelDiv">
</div>
URL: http://javierrey.com/project-locations-path/?v=0&amp;a=0&amp;t=0 (v: Speed Threshold, a: Acceleration Threshold, t: Time Scale)
<br/>Use 0 for default values: v: 3.65, a: 1.32, t: 15
<br/>Use -1 for no filtering, huge thresholds: v: 100, a: 100
<br/>Use -2 for full filtering, only stops: v: 0, a: 0
<br/>
</body>
</html>