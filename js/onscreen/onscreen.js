const OnScreen = function() {
  const _ = this;
  this.docElem = document.documentElement;
  this.selector = ".animate-in:not(.on-screen)";
  this.multiplier = 350;
  this.maxMultiplier = 2500;

  this.observer = new IntersectionObserver(function(entries) {
    _.processEntries(entries);
  });

  this.getMultiplier = function(i) {
    const m = _.multiplier * i;

    if (m > _.maxMultiplier) {
      const max = _.maxMultiplier;
      return max;
    }

    return m;
  };

  this.getRandomMultiplier = function(i) {
    const min = 0;
    let max = _.multiplier * i;
    let m = Math.random() * (+max - +min) + +min;

    if (m > _.maxMultiplier) {
      max = _.maxMultiplier;
      m = Math.random() * (+max - +min) + +min;
    }

    return m;
  };

  this.compareEntryY = function(a, b) {
    if (a.boundingClientRect.top < b.boundingClientRect.top) {
      return -1;
    }
    if (a.boundingClientRect.top > b.boundingClientRect.top) {
      return 1;
    }
    return 0;
  };

  this.processEntries = function(entries) {
    // Sort entries by y position.
    entries.sort(_.compareEntryY);

    for (let i = 0; i < entries.length; i++) {
      if (entries[i].isIntersecting) {
        const target = entries[i].target;
        let multiplier = _.getRandomMultiplier(i);

        setTimeout(_.unObserve.bind(_, target), multiplier);
      }
    }
  };

  this.setDone = function(el) {
    el.classList.add("done");
  };

  this.unObserve = function(el) {
    _.observer.unobserve(el);

    if (window.requestAnimationFrame) {
      window.requestAnimationFrame(function() {
        el.classList.add("on-screen");
      });
    } else {
      el.classList.add("on-screen");
    }
  };

  this.setObserver = function() {
    const els = document.querySelectorAll(_.selector);
    for (let i = 0; i < els.length; i++) {
      _.observer.observe(els[i]);
    }
  };

  this.throttledSetObserver = throttle(_.setObserver);

  if ("MutationObserver" in window) {
    new MutationObserver(_.throttledSetObserver).observe(_.docElem, {
      childList: true,
      subtree: true
    });
  } else {
    _.docElem.addEventListener("DOMNodeInserted", _.throttledSetObserver, true);
    _.docElem.addEventListener("DOMAttrModified", _.throttledSetObserver, true);
    setInterval(_.throttledRefreshAllMods, 999);
  }

  _.setObserver();
};

const os = new OnScreen();
