(function() {
  // loadJsonFile('/data/images.json', init);
  loadJsonFile('http://cdn.durka.plez.me/images.php', init);

  /**
   * Main entrypoint.
   *
   * In this place the data file is loaded already.
   *
   * @param data
   */
  function init(data) {
    let imageWrapper = document.getElementById('image-wrapper');
    let images = data.images;

    /**
     *
     * @param arra1
     * @returns {*}
     * @link https://www.w3resource.com/javascript-exercises/javascript-array-exercise-17.php
     */
    function shuffleArray(arra1) {
      let ctr = arra1.length, temp, index;

      // While there are elements in the array
      while (ctr > 0) {
        // Pick a random index
        index = Math.floor(Math.random() * ctr);
        // Decrease ctr by 1
        ctr--;
        // And swap the last element with it
        temp = arra1[ctr];
        arra1[ctr] = arra1[index];
        arra1[index] = temp;
      }
      return arra1;
    }

    function updateImage() {
      if (images.length === 0) {
        images = shuffleArray(data.images);
      }

      let imageInfo = images.pop();
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

      imgEl.setAttribute('height', document.documentElement.clientHeight - 100);

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