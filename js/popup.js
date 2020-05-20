const popupManager = function(el) {
  const _ = this;
  this.el = el;
  this.id = el.dataset.popup;
  this.cookie = 'neg_p_' + this.id;

  this.getCookie = function getCookie(name) {
    var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
    return v ? v[2] : null;
  };

  this.setCookie = function setCookie(name, value, days) {
    var d = new Date;
    d.setTime(d.getTime() + 24*60*60*1000*days);
    document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
  };

  this.handleClick = function handleClick(e) {
    const target = e.target;

    if (target === el || target.closest("button.close")) {
      e.preventDefault();
      e.stopPropagation();
      _.close();
    }
  };

  this.close = function close() {
    _.el.classList.remove("visible");
    _.el.removeEventListener("click", _.handleClick);
    _.setCookie(_.cookie, "shown", 1);
  };

  this.init = function init() {
    const cookie = _.getCookie(_.cookie);
    if (cookie === "shown") {
      return;
    }

    _.el.classList.add("visible");
    _.el.addEventListener("click", _.handleClick);
  };


  this.init();
};

const popups = document.querySelectorAll("body > article.popup");
Array.prototype.forEach.call(popups, function(el) { new popupManager(el) });
