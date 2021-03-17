(function() {
  const SearchSuggest = function(el){

    const _ = this;
    this.el = el;
    this.endpoint = null;

    var suggestion = "",
      search_input,
      last_search=false,
      search_suggestion,
      search_suggestion_helper,
      timerrs,
      xhr,
      search_cache
    ;

    this.triggerSearch = function triggerSearch() {
      const event = new CustomEvent("search_suggest_search", {
        detail: {
          'el': _.el,
          'value': _.search_input.value
        },
        bubbles: true,
        cancelable: true,
        composed: false,
      });
      _.el.dispatchEvent(event);
    };

    this.keyUp = function(e) {
      var code = (e.keyCode ? e.keyCode : e.which);

      if(code == 9 || code == 13){
        if(_.acceptSuggestion() == true){
          //accept suggestion
          // if tab then prevent default, otherwise it's ok to submit
          if(code == 9){
            e.preventDefault();
          }

          return false;
        }
        else {
          if(code == 13){
            _.triggerSearch();
            return false;
          }
        }

      }

      _.debouncedSuggest();

    }

    this.keyDown = function(e){
      var code = (e.keyCode ? e.keyCode : e.which);

      if(typeof(_.xhr) != "undefined"){
        //abort any previous operations
        _.xhr.abort();
      }

      if(code == 9 && !e.altKey) {
        e.preventDefault(); //keep tab key from focusing next input
        return;
      }
      else if (code == 13){
        if(_.suggestion.length > 0){
          //prevent default enter behavior if suggestion is being accepted
          e.preventDefault();
          return;
        }
      }
      else if(_.search_input.value == "" && code == 32){
        e.preventDefault();
        return;
      }

      _.clearSuggestion();
    }

    this.acceptSuggestion = function(){
      if(_.suggestion.length == 0){
        return false;
      }

      _.search_input.value = _.search_input.value.trim() + _.suggestion;
      _.clearSuggestion();

      return true;
    }

    this.setSuggestion = function(value){
      _.suggestion = value;

      _.search_suggestion_helper.innerHTML = _.search_input.value.replace(" ","&#32;");
      var width = _.search_suggestion_helper.offsetWidth;
      _.search_suggestion.innerHTML = value.trim();
      _.search_suggestion.style.paddingLeft = "calc("+ width + "px + .2em)";
    }

    this.clearSuggestion = function(){
      _.setSuggestion("");
    }

    this.debouncedSuggest = function(){
      var value = _.ltrim(_.search_input.value);

      //clear the timer
      _.timerrs && clearTimeout(_.timerrs);

      if(value == _.last_search){
        return;
      }

      _.clearSuggestion();

      if(value.length == 0){
        return;
      }

      _.timerrs = setTimeout(_.suggest, 40);
    }

    this.rtrim = function(str){
      return str.replace(/\s+$/,"");
    }

    this.ltrim = function(str, charlist) {
      //  discuss at: http://locutus.io/php/ltrim/
      // original by: Kevin van Zonneveld (http://kvz.io)
      //    input by: Erkekjetter
      // improved by: Kevin van Zonneveld (http://kvz.io)
      // bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
      //   example 1: ltrim('    Kevin van Zonneveld    ')
      //   returns 1: 'Kevin van Zonneveld    '

      charlist = !charlist ? ' \\s\u00A0' : (charlist + '')
        .replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^:])/g, '$1')

        var re = new RegExp('^[' + charlist + ']+', 'g')

        return (str + '')
        .replace(re, '')
    }

    this.requestSuggestion = function(){

      if(typeof(_.xhr) == "undefined"){
        _.xhr = new XMLHttpRequest();
      }

      _.xhr.abort();

      _.xhr.onreadystatechange = function(){
        if(_.xhr.readyState == 4){
          if(_.xhr.status == 200){
            if(_.xhr.responseText.length > 0){
              var data = JSON.parse(_.xhr.responseText);
              _.search_cache = data;
              _.suggest();
            }
          } else {
          }
        }
      }

      //open new request
      _.xhr.open('GET',_.endpoint);
      _.xhr.send(null);

    }

    this.splitKeys = function(value){
      value = _.ltrim(value);

      var res = value.match(/^(.*?)\s*([^\s]*)$/);
      return [ res[1], res[2] ];

    }

    this.suggest = function(){

      var value = _.ltrim(_.search_input.value);

      var keys = _.splitKeys(value);
      var complete = keys[0];
      var incomplete = keys[1];

      if(complete.length > 0){
        complete += " ";
      }

      _.last_search = value;

      if(incomplete == " " || incomplete.length == 0){
        //nothing to search
        return;
      }

      if(typeof(_.search_cache) == "undefined"){
        _.requestSuggestion();
        return;
      }


      var regex = new RegExp('^' + incomplete.replace(/[-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"), 'i');

      for(var i = 0, l = _.search_cache; i < l.length; i++){
        if(regex.test(l[i])){
          _.setSuggestion(l[i].slice(incomplete.length));
          break;
        }
      }

    }

    this.setup = function(){
      _.search_input = _.el.querySelector('input');
      _.search_suggestion = _.el.querySelector('.suggestion_overlay');
      _.search_suggestion_helper = _.el.querySelector('.helper');
      _.search_input.addEventListener('keyup',_.keyUp);
      _.search_input.addEventListener('keydown',_.keyDown);
      _.endpoint = _.el.dataset.endpoint;
    }

    this.ready = function(callback){
      if (document.readyState == "loaded") {
        callback();
      }
      else {
        document.addEventListener('DOMContentLoaded', function(){
          callback();
        });
      }
    }

    _.ready(_.setup);

  };

  const fields = document.querySelectorAll('.search-field.suggest');
  for (let i = 0; i < fields.length; i++) {
    let suggest = new SearchSuggest(fields[i]);
  }
})();
