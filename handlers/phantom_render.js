var page = require('webpage').create(),
    system = require('system'),
    address, output, size;

address = system.args[1];
output = system.args[2];

// Make page console print to stderr
page.onConsoleMessage = function(msg) {
    system.stderr.writeLine('console: ' + msg);
};


page.viewportSize = {width: 1680, height: 1050};

if(system.args.length < 3)
{
    console.log("USAGE: phantomjs phantom_render.js URL OUTPUT\nRenders at 1680x1050, as PNG or PDF (based on output file extension)");
    phantom.exit(1);
}

if (system.args[2].substr(-4) === ".pdf")
{
    page.paperSize = {format: "A4", orientation: 'landscape', margin: '1cm'};
    page.zoomFactor = 0.8;
            // {width: "30cm", height: "20cm", margin: '0px'}
}
else
{
    page.viewportSize = {width: 1680, height: 1050};
    page.zoomFactor = 1;
    //page.clipRect = {top: 0, left: 0, width: 1680, height: 1050};
}


// Send Chrome UA
page.settings.userAgent = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36";

page.open(address, function (status)
{
    if (status !== 'success')
    {
        console.log('Unable to load the address!');
        phantom.exit(1);
    }
    else
    {
        function waitFor(testFx, onReady, timeOutS)
        {
            var maxtimeOutMillis = timeOutS * 1000;
            var start = new Date().getTime();
            var done = false;
            interval = setInterval(function()
            {
                if(done) return;
                
                if ( (new Date().getTime() - start < maxtimeOutMillis))
                {
                    // If not time-out yet and condition not yet fulfilled
                    if(testFx())
                    {
                        done = true;
                        clearInterval(interval);
                        onReady();
                    }
                }
                else
                {
                    done = true;
                    clearInterval(interval);
                    onReady();
                }
            }, 250); //< repeat check every 250ms
        };

        var takeshot = function ()
        {
            // Scroll back to the top of the page
            page.evaluate(function()
            {
                window.document.body.scrollTop = 0;
            });
            
            // Wait for things to settle down and then render the output
            window.setTimeout(function(){
                page.render(output);
                phantom.exit();
            }, 2000);
        };

        var start = new Date().getTime();
        waitFor(function()
        {    
            return page.evaluate(function()
            {
                // Gradually scrolls to the bottom of page
                // Some sites only load content when it's visible!
                if(window.document.body.scrollTop < document.body.scrollHeight && window.document.body.scrollTop < 2000)
                {
                    window.document.body.scrollTop = window.document.body.scrollTop + 800;
                }
                
                var images = document.getElementsByTagName('img');

                for(var i in images)
                {
                    var img = images[i];
                    
                    if(typeof img.complete === 'undefined')
                        continue;

                    if(!images[i].complete)
                    {
                        return false;
                    }

                    console.log("Images loaded");
                    return true;
                }
            });
        }
        , takeshot, 15);
    }
});