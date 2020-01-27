(function() {
  loadJsonFile('/data/images.json', init);

  /**
   * Main entrypoint.
   *
   * In this place the data file is loaded already.
   *
   * @param data
   */
  function init(data) {
    let imageWrapper = document.getElementById('image-wrapper');

    function updateImage() {
      let images = data.images;
      let imageInfo = images[random(0, images.length - 1)];
      if (!imageInfo) {
        return;
      }

      let imgEl = document.createElement('img');
      if (imageInfo.src != null) {
        imgEl.setAttribute('src', imageInfo.src);
      }
      else if (imageInfo.data != null) {
        // @todo: Add this functionality.
      }
      else {
        return;
      }

      imageWrapper.innerHTML = "";
      imageWrapper.appendChild(imgEl);
    }

    updateImage();
  }

  window.random = function(min, max) {
    return Math.round(Math.random() * (max - min)) + min;
  }
})();