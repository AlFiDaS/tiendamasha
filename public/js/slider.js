document.addEventListener('DOMContentLoaded', function () {
    if (typeof Splide !== 'undefined') {
      new Splide('#hero-slider', {
        type: 'fade',
        autoplay: true,
        interval: 4000,
        rewind: true,
      }).mount();
    }
  });
  