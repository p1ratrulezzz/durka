(function() {
  loadJsonFile('/data/images.json', init);
  // loadJsonFile('http://localhost:8084/images.php', init);

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
      if (imageInfo.url != null) {
        imgEl.setAttribute('src', imageInfo.url);
      }
      else if (imageInfo.data != null) {
        imgEl.setAttribute('src', imageInfo.data);
      }
      else {
        return;
      }

      imgEl.setAttribute('height', document.documentElement.clientHeight);

      imgEl.addEventListener('click', updateImage);
      imageWrapper.innerHTML = "";
      imageWrapper.appendChild(imgEl);
    }

    updateImage();
  }

  window.random = function(min, max) {
    return Math.round(Math.random() * (max - min)) + min;
  }
})();