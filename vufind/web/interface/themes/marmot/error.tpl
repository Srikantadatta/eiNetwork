<!DOCTYPE html>
<html lang="en">
<meta charset="utf-8">
<head>
{literal}
<title>An Error has Occurred</title>
<style>


html { overflow:hidden; }
body { font: 60px 'SilkscreenNormal', Arial, sans-serif; letter-spacing:0; background:#25d; color:#fff; }

/* Thanks, http://www.colorzilla.com/gradient-editor/ */
#errorContainer {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	background: #083a7f;
	background: -moz-linear-gradient(top, #083a7f 0%, #242b3d 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#083a7f), color-stop(100%,#242b3d));
	background: -webkit-linear-gradient(top, #083a7f 0%,#242b3d 100%);
	background: -o-linear-gradient(top, #083a7f 0%,#242b3d 100%);
	background: -ms-linear-gradient(top, #083a7f 0%,#242b3d 100%);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#083a7f', endColorstr='#242b3d', GradientType=0);
	background: linear-gradient(top, #083a7f 0%,#242b3d 100%);
}

#errorContainer h1, #errorContainer h2 { margin:0; text-shadow:0 5px 0px rgba(0,0,0,.2); }
#errorContainer h1 { font-size:1em; }
#errorContainer h2 { font-size:.5em; }
#errorContainer a { color:#fff; }
#errorContainer h3 { font-size:.25em; margin:1em 50px; }
#errorContainer h3, #errorContainer h3 a { color:#6b778d; }
#errorContainer h3 img { margin:0 3px; }
#errorContainer h4 { font-size:.30em; margin:1em 50px; }
#errorContainer h4, #errorContainer h4 a { color:#ffffff; }

#title { position:absolute; top:50%; width:100%; height:322px; margin-top:-180px; text-align:center; z-index:10; }
#debug { position:absolute; top:75%; width:50%; margin-left:25%; height:20%; text-align:left; z-index:10;overflow:auto; font-size:11px; }
#debug h4 { font-size:13px; margin: 1em 0px;}
.cloud { position:absolute; display:block; }
.puff { position:absolute; display:block; width:15px; height:15px; background:white; opacity:.05; filter:alpha(opacity=5); }

</style>
<script>
function randomInt(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min
}

function randomChoice(items) {
	return items[randomInt(0, items.length-1)]
}

var PIXEL_SIZE = 25

function makeCloud() {
	var w = 8,
		h = 5,
		maxr = Math.sqrt(w*w + h*h),
		density = .4

	var cloud = document.createElement('div')
	cloud.className = 'cloud'
	for (var x=-w; x<=w; x++) {
		for (var y=-h; y<=h; y++) {
			r = Math.sqrt(x*x + y*y)
			if (r/maxr < Math.pow(Math.random(), density)) {
				var puff = document.createElement('div')
				puff.className = 'puff'
				puff.style.left = (x + w) * PIXEL_SIZE + 'px'
				puff.style.top = (y + h) * PIXEL_SIZE + 'px'
				cloud.appendChild(puff)
			}
		}
	}
	return cloud
}

clouds = []

function randomPosition(max) {
	return Math.round(randomInt(-400, max)/PIXEL_SIZE)*PIXEL_SIZE
}

function addCloud(randomLeft) {
	var cloudiness = .3

	if (Math.random() < cloudiness) {
		newCloud = {
			x: randomLeft ? randomPosition(document.documentElement.clientWidth) : -400,
			el: makeCloud()
		}

		newCloud.el.style.top = randomPosition(document.documentElement.clientHeight) + 'px'
		newCloud.el.style.left = newCloud.x + 'px'
		document.body.appendChild(newCloud.el)
		clouds.push(newCloud)
	}
}

function animateClouds() {
	var speed = 25

	addCloud()

	var newClouds = []
	for (var i=0; i<clouds.length; i++) {
		var cloud = clouds[i]
		cloud.x += speed

		if (cloud.x > document.documentElement.clientWidth) {
			document.body.removeChild(cloud.el)
		} else {
			cloud.el.style.left = cloud.x + 'px'
			newClouds.push(cloud)
		}
	}

	clouds = newClouds
}

function changeMessage(msg) {
	var msgEl = document.getElementById('message')
	msgEl.innerHTML = msg || randomChoice(messages)
}

function startMessages() {
	try {
		if (window.sessionStorage) {
			var times
			if (sessionStorage.times) {
				times = ++sessionStorage.times
			} else {
				times = sessionStorage.times = 0
			}
			
			messages.push(msg)
		}
	} catch (e) {}

	setInterval(function() { changeMessage() }, 15*1000)
}

messages = [
	
	'Perhaps you could read a book.',
	'Have you considered starting your novel?',
	'Librarians have answers to your questions!',
	'We are working very hard to bring you a better catalog',
	'Want to search our <a href="http://marmot.lib.overdrive.com">digital collection</a>?',
	'<a href="http://marmot.lib.overdrive.com">Watch a movie</a>'
]

function start() {
	if (arguments.callee.ran) { return; }
	arguments.callee.ran = true

	startMessages()
	setInterval(animateClouds, 2*1000)

	for (n=0; n<50; n++) {
		addCloud(true)
	}
}

if (document.addEventListener) {
	document.addEventListener('DOMContentLoaded', start, false)
}
window.onload = start
</script>
{/literal}

<body>
	<div id="errorContainer">
		<div id="title">
			
			<h1>Oops, an error occurred</h1>
			<h2>This error has been logged and we are working on a fix.</h2>
			<h4>{$error->getMessage()}</h4>
			<h4>{translate text="Please contact the Library Reference Department for assistance"}<br /></h4>
    	<h4><a href="mailto:{$supportEmail}">{$supportEmail}</a></h4>
			<h3> ___ </h3>
			<h3 id="message"></h3>
		</div>
		<div id ="debug">
			{if $debug}
			  <h4>{translate text="Debug Information"}</h4>
				<p class="errorStmt">{$error->getDebugInfo()}</p>
			  {assign var=errorCode value=$error->getCode()}
			  {if $errorCode}
			  <p class="errorMsg">{translate text="Code"}: {$errorCode}</p>
			  {/if}
			  <p>{translate text="Backtrace"}:</p>
			  {foreach from=$error->backtrace item=trace}
			    [{$trace.line}] {$trace.file}<br />
			  {/foreach}
			{/if}
		</div>
	</div>
</body>
</html>