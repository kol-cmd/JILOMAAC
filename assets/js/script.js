/* =========================================
   1. MOBILE MENU LOGIC (Moved to Top for Safety)
   ========================================= */
document.addEventListener('DOMContentLoaded', () => {
    const openBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('close-mobile-menu');
    const menuOverlay = document.getElementById('mobile-menu-overlay');

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            menuOverlay.classList.add('is-active');
        });
    }

    const closeMenu = () => {
        menuOverlay.classList.remove('is-active');
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }

    if (menuOverlay) {
        menuOverlay.addEventListener('click', (e) => {
            if (e.target === menuOverlay) closeMenu();
        });
    }
});


/* =========================================
   2. HERO CAROUSEL LOGIC (Main Slider)
   ========================================= */
const heroTrack = document.querySelector(".carousel-track");
const heroSlides = heroTrack ? heroTrack.querySelectorAll(".slide") : [];
const heroNextBtn = document.querySelector(".nav-btn.next-btn");
const heroPrevBtn = document.querySelector(".nav-btn.prev-btn");
const indicatorsContainer = document.querySelector(".indicators");

let currentIndex = 0;
const totalSlides = heroSlides.length;
let autoPlayTimer;
let isInteracting = false; 

if (heroTrack && heroSlides.length > 0) {
  function setupIndicators() {
    indicatorsContainer.innerHTML = "";
    heroSlides.forEach((_, index) => {
      const dot = document.createElement("div");
      dot.classList.add("indicator");
      dot.innerHTML = `<div class="fill"></div>`;

      dot.addEventListener("click", () => {
        const scrollAmount = index * heroTrack.offsetWidth;
        heroTrack.scrollTo({
          left: scrollAmount,
          behavior: "smooth",
        });
      });
      indicatorsContainer.appendChild(dot);
    });
  }

  heroTrack.addEventListener("scroll", () => {
    const newIndex = Math.round(heroTrack.scrollLeft / heroTrack.offsetWidth);
    if (newIndex !== currentIndex) {
      currentIndex = newIndex;
      updateIndicatorsVisuals();
      handleSlideContent();
    }
  });

  function updateIndicatorsVisuals() {
    const allIndicators = document.querySelectorAll(".indicator"); 

    allIndicators.forEach((indicator, index) => {
      const fill = indicator.querySelector(".fill");

      if (index === currentIndex) {
        indicator.classList.add("active"); 
      } else {
        indicator.classList.remove("active"); 
      }

      fill.classList.remove("animate", "full");

      if (index < currentIndex) {
        fill.classList.add("full");
      }
    });
  }

  function handleSlideContent() {
    clearTimeout(autoPlayTimer);

    const currentSlide = heroSlides[currentIndex];
    const video = currentSlide.querySelector("video");
    const currentDotFill = document.querySelectorAll(".indicator .fill")[currentIndex];

    if (currentDotFill) {
      currentDotFill.classList.remove("animate", "full");
      void currentDotFill.offsetWidth; 
    }

    heroTrack.querySelectorAll("video").forEach((v) => {
      v.pause();
      v.currentTime = 0;
    });

    if (video) {
      if (currentDotFill) currentDotFill.classList.add("full"); 

      video.muted = true;
      var playPromise = video.play();
      if (playPromise !== undefined) {
        playPromise.catch((error) => console.log("Autoplay blocked"));
      }

      video.onended = () => {
        if (!isInteracting) scrollToNext();
      };

    } else {
      if (currentDotFill) currentDotFill.classList.add("animate"); 

      autoPlayTimer = setTimeout(() => {
        if (!isInteracting) scrollToNext();
      }, 5000);
    }
  }

  function scrollToNext() {
    if (currentIndex >= totalSlides - 1) {
      heroTrack.scrollTo({ left: 0, behavior: "smooth" });
    } else {
      heroTrack.scrollBy({ left: heroTrack.offsetWidth, behavior: "smooth" });
    }
  }

  if (heroNextBtn) {
    heroNextBtn.addEventListener("click", () => {
      isInteracting = true; 
      scrollToNext();
      setTimeout(() => (isInteracting = false), 1000);
    });
  }

  if (heroPrevBtn) {
    heroPrevBtn.addEventListener("click", () => {
      isInteracting = true;
      if (currentIndex === 0) {
        heroTrack.scrollTo({ left: heroTrack.scrollWidth, behavior: "smooth" });
      } else {
        heroTrack.scrollBy({
          left: -heroTrack.offsetWidth,
          behavior: "smooth",
        });
      }
      setTimeout(() => (isInteracting = false), 1000);
    });
  }

  setupIndicators();
  setTimeout(() => {
    updateIndicatorsVisuals();
    handleSlideContent();
  }, 100);
}


/* =========================================
   3. MAGIC 3D CAROUSEL LOGIC
   ========================================= */
let magicNextBtn = document.getElementById("next");
let magicPrevBtn = document.getElementById("prev");
let magicBackBtn = document.getElementById("back");
let magicCarousel = document.querySelector(".carousel");
let magicListHTML = document.querySelector(".carousel .list");

if (magicNextBtn && magicPrevBtn && magicCarousel && magicListHTML) {
  let unAcceptClick;

  const showSlider = (type) => {
    magicNextBtn.style.pointerEvents = "none";
    magicPrevBtn.style.pointerEvents = "none";

    magicCarousel.classList.remove("prev", "next");
    let items = document.querySelectorAll(".carousel .list .item");

    if (type === "next") {
      magicListHTML.appendChild(items[0]);
      magicCarousel.classList.add("next");
    } else {
      let positionLast = items.length - 1;
      magicListHTML.prepend(items[positionLast]);
      magicCarousel.classList.add("prev");
    }

    clearTimeout(unAcceptClick);
    unAcceptClick = setTimeout(() => {
      magicNextBtn.style.pointerEvents = "auto";
      magicPrevBtn.style.pointerEvents = "auto";
    }, 2000);
  };

  magicNextBtn.onclick = function () {
    showSlider("next");
  };

  magicPrevBtn.onclick = function () {
    showSlider("prev");
  };

  let touchStartX = 0;
  let touchEndX = 0;

  magicCarousel.addEventListener('touchstart', e => {
      touchStartX = e.changedTouches[0].screenX;
  }, {passive: true});

  magicCarousel.addEventListener('touchend', e => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
  }, {passive: true});

  function handleSwipe() {
      const swipeThreshold = 50; 
      if (touchStartX - touchEndX > swipeThreshold) {
          showSlider("next");
      } else if (touchEndX - touchStartX > swipeThreshold) {
          showSlider("prev");
      }
  }

  magicCarousel.addEventListener("click", (e) => {
    const btn = e.target.closest(".seeMore");
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();
    magicCarousel.classList.add("showDetail");
  });

  if (magicBackBtn) {
    magicBackBtn.onclick = function () {
      magicCarousel.classList.remove("showDetail");
    };
  }
}


/* =========================================
   4. SEARCH LOGIC
   ========================================= */
document.addEventListener("DOMContentLoaded", function() {
    const searchIcon = document.querySelector(".svgcon");
    const searchContainer = document.querySelector(".search");
    const closeBtn = document.querySelector(".x");
    const searchInput = document.querySelector(".bar input");

    const productGrid = document.getElementById("productGrid");
    const featuredTitle = document.getElementById("featuredTitle");
    const hotSalesGrid = document.getElementById("hotSalesGrid");
    const hotSalesTitle = document.getElementById("hotSalesTitle");

    if (searchIcon) {
        searchIcon.addEventListener("click", () => {
            searchContainer.classList.add("active");
            if(searchInput) searchInput.focus();

            let targetTitle = hotSalesTitle || featuredTitle;
            if (targetTitle) {
                setTimeout(() => {
                    targetTitle.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300); 
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            searchContainer.classList.remove("active");
            if(searchInput) searchInput.value = ""; 
            
            if (hotSalesGrid) performSearch(hotSalesGrid, hotSalesTitle, "Hot Sales");
            if (productGrid) performSearch(productGrid, featuredTitle, "FEATURED PRODUCTS");
        });
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", function (e) {
            let searchTerm = this.value;

            if (e.key === "Enter") {
                if (searchTerm.trim() !== "") {
                    let urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('q', searchTerm);
                    window.location.href = "products.php?" + urlParams.toString();
                }
                return; 
            }

            if (productGrid) {
                performSearch(productGrid, featuredTitle, "FEATURED PRODUCTS");
            } 
            else if (hotSalesGrid) {
                hotSalesGrid.classList.remove('opacity-100');
                hotSalesGrid.classList.add('opacity-50');
                performSearch(hotSalesGrid, hotSalesTitle, "Hot Sales");
            }
        });
    }

    function performSearch(grid, titleElement, defaultTitleText) {
        let searchTerm = searchInput ? searchInput.value : "";
        let urlParams = new URLSearchParams(window.location.search);
        let activeBrand = urlParams.get('brand') || '';
        let activeCategory = urlParams.get('category') || document.body.dataset.category || '';
        let activeSort = urlParams.get('sort') || 'newest';

        let ajaxUrl = "search_ajax.php?q=" + encodeURIComponent(searchTerm) + 
                      "&brand=" + encodeURIComponent(activeBrand) + 
                      "&category=" + encodeURIComponent(activeCategory) + 
                      "&sort=" + encodeURIComponent(activeSort);

        fetch(ajaxUrl)
            .then((response) => response.text())
            .then((data) => {
                grid.innerHTML = data;

                if (searchTerm.length > 0) {
                    let brandText = activeBrand ? activeBrand + " " : "";
                    titleElement.innerText = "Results for: " + searchTerm.toUpperCase() + " in " + brandText + "Store";
                } else {
                    titleElement.innerText = defaultTitleText;
                }

                setTimeout(() => {
                    grid.classList.remove('opacity-50');
                    grid.classList.add('opacity-100');
                }, 150);
            })
            .catch((error) => console.error("Error:", error));
    }

    // Secondary Search Form (if used)
    const searchForm = document.getElementById('searchForm');
    const secondaryInput = document.getElementById('searchInput');

    if (searchForm) {
        searchForm.addEventListener('submit', function(e) { 
            e.preventDefault(); 
            if(productGrid) performSearch(productGrid, featuredTitle, "FEATURED PRODUCTS"); 
        });
    }
});


/* =========================================
   5. THEME TOGGLE (CRASH FIXED)
   ========================================= */
const themeToggleBtn = document.getElementById("theme-toggle");

// Only run theme code if the button actually exists on the page
if (themeToggleBtn) {
  const themeIcon = themeToggleBtn.querySelector("span");
  const html = document.documentElement;

  function updateTheme(isDark) {
    if (isDark) {
      html.classList.add("dark");
      if(themeIcon) themeIcon.textContent = "light_mode";
      localStorage.setItem("theme", "dark");
    } else {
      html.classList.remove("dark");
      if(themeIcon) themeIcon.textContent = "dark_mode";
      localStorage.setItem("theme", "light");
    }
  }

  const savedTheme = localStorage.getItem("theme") === "dark";
  updateTheme(savedTheme);

  themeToggleBtn.addEventListener("click", () => {
    const isDark = html.classList.contains("dark");
    updateTheme(!isDark);
  });
}