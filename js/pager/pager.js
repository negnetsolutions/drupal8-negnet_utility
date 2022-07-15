var Pager = function (el, opts) {
  var _ = this;
  this.autopager = false;
  this.el = el;
  this.pager = this.el.querySelector('.negnet_utility_pager');
  this.xobj = new XMLHttpRequest();
  this.xobj.overrideMimeType("application/json");
  this.pagerItems = null;

  if (this.pager) {
    this.pagerItems = this.pager.querySelector('ul');
  }

  this.opts = (typeof(opts) !== "undefined") ? opts : {};
  this.itemsEl = el.querySelector('.items');

  if (!this.itemsEl) {
    return;
  }

  this.itemsContainer = this.itemsEl.querySelector('.items-container');

  if (!this.itemsContainer) {
    this.itemsContainer = this.itemsEl;
  }

  this.url = this.itemsEl.dataset.endpoint;

  if (typeof this.url === 'undefined') {
    return;
  }

  this.fetchOptions = function fetchOptions() {
    const options = {};
    for (let d in this.itemsEl.dataset) {
      if (!['endpoint', 'totalitems'].includes(d)) {
        options[d] = _.itemsEl.dataset[d];
      }
    }

    return options;

  };

  this.currentPage = 0;
  this.total = _.itemsEl.dataset.totalitems;
  this.totalPages = Math.floor(this.total / _.itemsEl.dataset.perpage);
  this.firstPage = (_.itemsContainer.children.length > 0) ? 1 : 0;

  this.fetch = function (page) {
    _.currentPage = parseInt(page);
    const endpoint = _.getUrl() + "&page=" + page;

    _.buildPager();

    history.pushState(null, null, _.buildUrl(page));

    _.itemsContainer.classList.add("dim");

    _.xobj.open('GET', endpoint, true); // Replace 'my_data' with the path to your file
    _.xobj.onreadystatechange = function () {
      if (_.xobj.readyState == 4 && _.xobj.status == "200") {
        var data = JSON.parse(_.xobj.responseText);

        if (parseInt(data.count) != parseInt(_.total)) {
          _.total = data.count;
          _.totalPages = Math.floor(_.total / _.itemsEl.dataset.perpage);
          _.buildPager();
        }

        var fragment = document.createDocumentFragment();
        for (var i = 0; i < data.items.length; i++) {
          var item = data.items[i];
          var el = createElementFromHTML(item);

          fragment.appendChild(el);
        }

        // Run any scripts included.
        let scripts = fragment.querySelectorAll('script');
        for (let n = 0; n < scripts.length; n++) {
          eval(scripts[n].innerHTML)
        }

        if (_.pager) {
          const currentLink = _.pager.querySelector('.is-active');
          if (currentLink) {
            currentLink.classList.remove('Loader');
          }
        }

        window.requestAnimationFrame(function() {
          _.itemsContainer.innerHTML = '';
          _.itemsContainer.appendChild(fragment);
          _.itemsContainer.classList.remove("dim");
          _.el.scrollIntoView({ block: 'start',  behavior: 'smooth' });
        });
      }
    };
    _.xobj.send(null);
  }

  this.buildUrl = function(page) {
    let path = window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);

    if (page == 0) {
      urlParams.delete('page');
    }

    const params = urlParams.toString();

    return path + ((params.length > 0) ? '?' : '') + params;
  };

  this.buildPager = function() {

    const fragment = document.createDocumentFragment();
    const totalPagesShown = parseInt(9);

    if (parseInt(_.currentPage) > 0) {
      let el = createElementFromHTML("<li><a href='" + _.buildUrl(0) + "' class='pager__item pager__item--first' data-page='0'>‹‹ First</a></li>");
      fragment.appendChild(el);
    }

    if ((parseInt(_.currentPage) - 1) >= 0) {
      el = createElementFromHTML("<li><a href='" + _.buildUrl(parseInt(_.currentPage) - 1) + "' class='pager__item pager__item--prev' rel='prev' data-page='" + (parseInt(_.currentPage) - 1) + "'>‹‹</a></li>");
      fragment.appendChild(el);
    }

    let middle = Math.ceil(totalPagesShown / 2);
    let current = parseInt(_.currentPage);
    let first = current - middle;
    let last = current + totalPagesShown - middle;
    let max = parseInt(_.totalPages);

    let i = first;
    if (last > max) {
      i = i + (max - last);
      last = max;
    }

    if (i < 0) {
      last = last + (1 - i);
      i = 0;
    }

    if (i != max && i > 0) {
      el = createElementFromHTML("<li>…</li>");
      fragment.appendChild(el);
    }

    for (; i <= last && i <= max; i++) {
      let el = createElementFromHTML("<li><a href='" + _.buildUrl(i) + "' class='pager__item" + ((i == parseInt(_.currentPage)) ? ' is-active Loader' : '') + "' data-page='" + i +"'>" + (i + 1) + "</a></li>");
      fragment.appendChild(el);
    }

    if (last < parseInt(_.totalPages)) {
      el = createElementFromHTML("<li>…</li>");
      fragment.appendChild(el);
    }

    if ((current + 1) <= parseInt(_.totalPages)) {
      el = createElementFromHTML("<li><a href='" + _.buildUrl(parseInt(_.currentPage) + 1) + "' class='pager__item pager__item--next' rel='next' data-page='" + (parseInt(_.currentPage) + 1) + "'>››</a></li>");
      fragment.appendChild(el);
    }

    if (parseInt(_.currentPage) != (parseInt(_.totalPages))) {
      let el = createElementFromHTML("<li><a href='" + _.buildUrl(parseInt(_.totalPages)) + "' class='pager__item pager__item--last' data-page='" + (parseInt(_.totalPages)) + "'>Last ››</a></li>");
      fragment.appendChild(el);
    }

    if (_.pagerItems) {
      _.pagerItems.innerHTML = '';
      _.pagerItems.appendChild(fragment);
    }
  };

  this.getUrl = function() {

    var str = "";
    const options = _.fetchOptions();
    for (var key in options) {
      str += "&";
      str += key + "=" + encodeURIComponent(options[key]);
    }

    if (typeof(_.opts.fetchOptionsCallback) !== "undefined") {
      var opts = _.opts.fetchOptionsCallback.call(_.opts.fetchOptionsCallbackContext);
      for (var key in opts) {
        str += "&";
        str += key + "=" + encodeURIComponent(opts[key]);
      }
    }

    const sep = (_.url.includes('?')) ? '&' : '?';
    return _.url + sep + str.substring(1);
  }

  window.addEventListener('popstate', function(event) {
    const urlParams = new URLSearchParams(window.location.search);
    let page = urlParams.get('page');
    if (!page) {
      page = 0;
    }
      _.fetch(page);
  });

  if (this.pager) {
    _.pager.addEventListener('click', function (e) {
      const link = e.target.closest('a');

      if (link) {
        e.preventDefault();
        e.stopPropagation();
        _.fetch(link.dataset.page);
      }
    });
  }

  if (_.firstPage == 0) {
    _.fetch(0);
  }

};

window.pagers = new function() {
  const _ = this;
  this.pagers = [];

  Array.prototype.forEach.call(document.querySelectorAll(".ajax-paged"), function(el) { _.pagers.push(new Pager(el)) });

  this.getPagerByEl = function getPagerByEl(el) {

    for (let i = 0; i < _.pagers.length; i++) {
      if (_.pagers[i].el === el) {
        return _.pagers[i];
      }
    }

    return null;
  };

}();
