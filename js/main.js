(function() {
  // loadJsonFile('/data/images.json', init);
  loadJsonFile('https://cdn.durka.plez.me/images.php', init);

  /**
   * Main entrypoint.
   *
   * In this place the data file is loaded already.
   *
   * @param data
   */
  function init(data) {
    let imageWrapper = document.getElementById('image-wrapper');
    let images = [];

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
      if (images.length < 5) {
        images = shuffleArray(data.images);
        imageWrapper.innerHTML = "";
      }

      let imgEls = imageWrapper.getElementsByTagName('img');
      let length = imgEls.length;
      if (length > 0) {
        imgEls[0].remove();
        length--;
      }

      for (let i=length; i < 5; i++) {
        let imageInfo = images.pop();
        if (!imageInfo) {
          return;
        }

        let imgEl = document.createElement('img');
        if (imageInfo.url != null) {
          imgEl.setAttribute('src', imageInfo.url);
        } else if (imageInfo.data != null) {
          imgEl.setAttribute('src', imageInfo.data);
        } else {
          return;
        }

        imgEl.style.display = 'none';

        let clientWidth = document.documentElement.clientWidth - 50;
        let clientHeight = document.documentElement.clientHeight - 50;
        if (imageInfo.width != null && imageInfo.height != null) {
          /*
           * width = height
           * widthInBrowser = newheight
           */
          let widthInBrowser = Math.floor(clientHeight * imageInfo.width / imageInfo.height);

          if (widthInBrowser < clientWidth) {
            imgEl.setAttribute('width', widthInBrowser);
          }
          else {
            imgEl.setAttribute('height', clientHeight);
          }
        }
        else {
          imgEl.setAttribute('height', clientHeight);
        }

        imgEl.addEventListener('click', updateImage);
        imageWrapper.appendChild(imgEl);
      }

      imageWrapper.getElementsByTagName('img')[0].style.display = null;
    }

    updateImage();
  }

  window.random = function(min, max) {
    return Math.round(Math.random() * (max - min)) + min;
  }
})();