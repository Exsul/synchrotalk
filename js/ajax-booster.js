var ajax_booster =
{
  init()
  {
    phoxy.state.ajax.booster = {};
    phoxy.state.ajax.current_threads = 0;
    phoxy.state.ajax.sheduled = 0;

    // http://www.browserscope.org/?category=network&v=top
    var web_requests_per_domain = 6;

    // Let browser have grasp for other requests
    phoxy.state.ajax.soft_cap = web_requests_per_domain - 1;

    // Do not exceed cap a lot
    phoxy.state.ajax.hard_cap = web_requests_per_domain * 1.5;

    phoxy.state.ajax.queue = [];


    phoxy._.internal.Override(phoxy._.api, 'ajax', function _ajax_booster(url, cb)
    {
      ajax_booster.request(url, cb);
    });

    phoxy._.internal.Override(EJS, 'request', function _ajax_booster(url, cb)
    {
      if (typeof cb !== 'function')
        return _ajax_booster.origin(url, cb); // we cant help here it's synchronous

      ajax_booster.schedule(url, cb);
    });
  }
  ,
  request: function(url, cb, data)
  {
    if (this.can_soft_shedule())
      return this.schedule(url, cb, data);

    this.push_to_queue(url, cb, data);
  }
  ,
  low_priority_request: function(url, cb, data)
  {
    console.log('resheduling', phoxy.state.ajax.current_threads);

    // if download not active, just start
    if (!phoxy.state.ajax.current_threads)
      return this.request(url, cb, data);

    this.push_to_queue(url, cb, data, "front");
  }
  ,
  schedule: function(url, cb, data)
  {
    phoxy.state.ajax.sheduled++;
    this.update_badge();

    var task =
    {
      cb: cb,
      data: data,
    };

    if (typeof phoxy.state.ajax.booster[url] != 'undefined')
      return phoxy.state.ajax.booster[url].push(task);

    phoxy.state.ajax.current_threads++;
    phoxy.state.ajax.booster[url] = [task];
    this.execute_next(url);
  }
  ,
  execute_next: function(url)
  {
    var task = phoxy.state.ajax.booster[url].shift();

    var shedule_next = function()
    {
      ajax_booster.finished(url);
      task.cb.apply(phoxy, arguments);
    };

    this.ajax(url, shedule_next, task.data);
  }
  ,
  finished: function(url)
  {
    phoxy.state.ajax.sheduled--;
    this.update_badge();

    // Should check for this.can_soft_shedule())
    for (var i = 0; i < phoxy.state.ajax.booster[url].length; i++)
    {
      var task = phoxy.state.ajax.booster[url][i];
      this.ajax(url, task.cb, task.data);
    }

    delete phoxy.state.ajax.booster[url];
    phoxy.state.ajax.current_threads--;

    this.schedule_from_queue();
  }
  ,
  ajax: function(url, cb, data)
  {
    if (url === undefined)
      return cb("");

    phoxy._.EarlyStage.ajax(url, cb, data);
  }
  ,
  push_to_queue: function(url, cb, data, front)
  {
    var task =
    {
      url: url,
      cb: cb,
      data: data,
    };

    if (front)
      phoxy.state.ajax.queue.unshift(task);
    else
      phoxy.state.ajax.queue.push(task);
  }
  ,
  pull_from_queue: function()
  {
    // we're using FILO for responsiveness on user actions
    // experimental feature
    return phoxy.state.ajax.queue.pop();
  }
  ,
  can_soft_pull: function()
  {
    if (phoxy.state.ajax.queue.length == 0)
      return false;

    return phoxy.state.ajax.current_threads <= phoxy.state.ajax.hard_cap;
  }
  ,
  can_soft_shedule: function()
  {
    return phoxy.state.ajax.current_threads <= phoxy.state.ajax.soft_cap;
  }
  ,
  schedule_from_queue: function()
  {
    while (this.can_soft_pull())
    {
      var task = this.pull_from_queue();
      this.schedule(task.url, task.cb, task.data);
    }
  }
  ,
  update_badge: function()
  {
    if (ajax_booster.timer)
      return;

    ajax_booster.timer = setTimeout(function()
    {
      var value = phoxy.state.ajax.current_threads + phoxy.state.ajax.queue.length;

      $('#state').trigger('state.update', ['ajax', value]);

      ajax_booster.timer = undefined;
    }, 300);

  }
};

ajax_booster.init();
