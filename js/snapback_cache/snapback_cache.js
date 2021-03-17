const SnapbackCache = (function(options) {
  var options = options || {}

  var SessionStorageHash = (function() {
    var set = function(namespace, key, item){
      var storageHash = sessionStorage.getItem(namespace);
      if (!storageHash) {
        storageHash = {}
      } else {
        storageHash = JSON.parse(storageHash)
      }

      if (item) {
        storageHash[key] = JSON.stringify(item)
      } else {
        delete storageHash[key]
      }

      sessionStorage.setItem(namespace, JSON.stringify(storageHash))
    }

    var get = function(namespace, key, item){
      var storageHash = sessionStorage.getItem(namespace)

      if(storageHash){
        storageHash = JSON.parse(storageHash)
        if(storageHash[key]){
          return JSON.parse(storageHash[key])
        }
      }

      return null
    }

    return {
      set: set,
      get: get
    }
  })()

  var enabled = true

  var disable = function() {
    enabled = false
  }

  var enable = function () {
    enabled = true
  }

  var supported = function(){
    return !!(sessionStorage && history && enabled && window.performance)
  }

  var setItem = function(url, value){
    if(value){
      // only keep 10 things cached
      trimStorage()
    }
    SessionStorageHash.set("pageCache", url, value)
  }

  var getItem = function(url){
    return SessionStorageHash.get("pageCache", url)
  }

  var removeItem = function(url){
    setItem(url, null)
  }

  var cachePage = function(){
    if (!supported()){
      return;
    }

    if (typeof options.wait === "function")
      options.finish()

    // Give transitions/animations a chance to finish
    const cachedBody = options.bodySelectorEl;

    const cachedPage = {
      body: cachedBody.innerHTML,
      title: document.title,
      positionY: window.pageYOffset,
      positionX: window.pageXOffset,
      cachedAt: new Date().getTime()
    }

    setItem(document.location.href, cachedPage)
  }

  var loadFromCache = function(noCacheCallback){
    // Check if there is a cache and if its less than 15 minutes old
    if(willUseCacheOnThisPage()){
      var cachedPage = getItem(document.location.href)

      // replace the content and scroll
      const body = options.bodySelectorEl;
      body.innerHTML = cachedPage.body;

      setTimeout(function(){
        window.scrollTo(cachedPage.positionX, cachedPage.positionY)
      }, 1);

      // pop the cache
      removeItem(document.location.href)

      return false;
    }
    else{
      if(noCacheCallback){
        noCacheCallback()
      }
      else{
        return
      }
    }
  }

  var trimStorage = function(){
    var storageHash = sessionStorage.getItem("pageCache");
    if(storageHash){
      storageHash = JSON.parse(storageHash);

      var tuples = [];

      for (var key in storageHash) {
        tuples.push([key, storageHash[key]])
      }
      // if storage is bigger than size, sort them, and remove oldest
      if(tuples.length >= 10){
        tuples.sort(function(a, b) {
            a = a[1].cachedAt;
            b = b[1].cachedAt;
            return b < a ? -1 : (b > a ? 1 : 0);
        });

        for (var i = 0; i < (tuples.length + 1 - 10); i++) {
          var key = tuples[i][0];
          delete storageHash[key];
        }

        sessionStorage.setItem(namespace, JSON.stringify(storageHash));
      }
    }
  }

  var willUseCacheOnThisPage = function(){
    if (!supported()){
      return false;
    }

    // if (performance.navigation.type != performance.navigation.TYPE_BACK_FORWARD) {
    //   return false;
    // }

    var cachedPage = getItem(document.location.href)

    // Check if there is a cache and if its less than 15 minutes old
    if(cachedPage && cachedPage.cachedAt > (new Date().getTime()-900000)){
      return true; 
    }
    else{
      return false;
    }
  }


  window.addEventListener('load', function(event) {
    loadFromCache()
  });

  return {
    enable: enable,
    disable: disable,
    remove: removeItem,
    loadFromCache: loadFromCache,
    cachePage: cachePage,
    willUseCacheOnThisPage: willUseCacheOnThisPage
  }
});
