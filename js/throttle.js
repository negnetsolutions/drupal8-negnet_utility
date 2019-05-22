var rAF = (function(){
  var running, waiting;
  var fns = [];

  var run = function(){
    var fn;
    running = true;
    waiting = false;
    while(fns.length){
      fn = fns.shift();
      fn[0].apply(fn[1], fn[2]);
    }
    running = false;
  };

  return function(fn){
    if(running){
      fn.apply(this, arguments);
    } else {
      fns.push([fn, this, arguments]);

      if(!waiting){
        waiting = true;
        (document.hidden ? setTimeout : requestAnimationFrame)(run);
      }
    }
  };
})();

var rAFIt = function(fn, simple){
  return simple ?
    function() {
      rAF(fn);
    } :
    function(){
      var that = this;
      var args = arguments;
      rAF(function(){
        fn.apply(that, args);
      });
    }
  ;
};

var throttle = function(fn){
  var running;
  var lastTime = 0;
  var gDelay = 125;
  var RIC_DEFAULT_TIMEOUT = 999;
  var rICTimeout = RIC_DEFAULT_TIMEOUT;
  var run = function(){
    running = false;
    lastTime = Date.now();
    fn();
  };
  var idleCallback = ('requestIdleCallback' in window) ?
    function(){
      requestIdleCallback(run, {timeout: rICTimeout});
      if(rICTimeout !== RIC_DEFAULT_TIMEOUT){
        rICTimeout = RIC_DEFAULT_TIMEOUT;
      }
    }:
    rAFIt(function(){
      setTimeout(run);
    }, true)
  ;

  return function(isPriority){
    var delay;
    if((isPriority = isPriority === true)){
      rICTimeout = 66;
    }

    if(running){
      return;
    }

    running =  true;

    delay = gDelay - (Date.now() - lastTime);

    if(delay < 0){
      delay = 0;
    }

    if(isPriority || (delay < 9 && ('requestIdleCallback' in window))){
      idleCallback();
    } else {
      setTimeout(idleCallback, delay);
    }
  };
};
