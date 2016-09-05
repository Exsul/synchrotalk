var warmup_obj =
{
  wait: true,
  config: "/api/phoxy",
  skip_initiation: true,
  sync_cascade: true,
  OnWaiting: function()
  {
    phoxy._.EarlyStage.async_require[0] = "/enjs.js";
    phoxy._.EarlyStage.async_require.push("/vendor/components/jquery/jquery.min.js");
    phoxy._.EarlyStage.EntryPoint();
  },
  OnBeforeCompile: function()
  {
  },
  OnAfterCompile: function()
  {
    phoxy.Config()['api_dir'] = '/' + phoxy.Config()['api_dir'];
    phoxy.Config()['ejs_dir'] = '/' + phoxy.Config()['ejs_dir'];
    phoxy.Config()['js_dir'] = '/' + phoxy.Config()['js_dir'];

    phoxy.Override('ApiAnswer', function not_found(data)
    { // 404 page overriding example
      if (data["error"] === 'Module not found'
          || data["error"] === "Unexpected RPC call (Module handler not found)")
      {
        $('.removeafterload').remove();

        if (typeof message != 'function')
          return phoxy.ApiRequest("utils/page404");

        return message('error', 'Some requests are failed');
      }
      return arguments.callee.origin.apply(this, arguments);
    })

    phoxy.Override('ApiAnswer', function show_server_log(data)
    {
      if (data["warnings"])
        phoxy.Log(2, "Server palm off", data["warnings"]);

      return arguments.callee.origin.apply(this, arguments);
    })

    phoxy.Log(3, "Phoxy ready. Starting");
  },
  OnBeforeFirstApiCall: function()
  {
  },
  OnExecutingInitialClientCode: function()
  {
    // Enable jquery in EJS context
    var origin_hook = EJS.Canvas.prototype.hook_first;
    EJS.Canvas.prototype.hook_first = function jquery_hook_first()
    {
      return $(origin_hook.apply(this, arguments));
    }
  },
  OnInitialClientCodeComplete: function()
  {
    phoxy.Log(3, "Initial handlers complete");
    $('.removeafterload').remove();
  }
  ,
  OnFirstPageRendered: function()
  {
    phoxy.Log(3, "First page rendered");
  }
};

if (typeof phoxy === 'undefined' || typeof phoxy.prestart === 'undefined')
  phoxy = warmup_obj;
else
{
  phoxy.prestart = warmup_obj;
  phoxy.prestart.OnWaiting();
}

(function()
{
  var d = document;
  var js = d.createElement("script");
  js.type = "text/javascript";
  js.src = "/phoxy/phoxy.js";
  js.setAttribute("async", "");
  d.head.appendChild(js);
})();


// Loading animation
(function()
{
  if (typeof phoxy._ === 'undefined')
    return setTimeout(arguments.callee, 10);

  var percents = phoxy._.EarlyStage.LoadingPercentage();
  var element = document.getElementById('percent');

  if (element === null)
    return;
  element.style.width = percents + "px";
  element.style.opacity = percents / 100 + 0.5;
  setTimeout(arguments.callee, 50);

  if (percents === 100)
    $('.removeafterload').css('opacity', 0);
})();
