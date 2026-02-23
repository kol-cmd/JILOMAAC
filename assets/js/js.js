
let nextBtn = document.getElementById("next");
let prevBtn = document.getElementById("prev");
let backBtn = document.getElementById("back");
let seeMoreBtn = document.querySelectorAll(".seeMore");
let carousel = document.querySelector(".carousel");
let listHTML = document.querySelector(".carousel  .list");

nextBtn.onclick = function () {
  showSlider("next");
};
prevBtn.onclick = function () {
  showSlider("prev");
};
let unAcceptClick;
const showSlider = (type) => {
  nextBtn.style.pointerEvents = "none";
  prevBtn.style.pointerEvents = "none";

  carousel.classList.remove("prev", "next");
  let items = document.querySelectorAll(".carousel .list .item");
  if (type === "next") {
    listHTML.appendChild(items[0]);
    carousel.classList.add("next");
  } else {
    let positionLast = items.length - 1;
    listHTML.prepend(items[positionLast]);
    carousel.classList.add("prev");
  }
  clearTimeout(unAcceptClick);
  unAcceptClick = setTimeout(() => {
    nextBtn.style.pointerEvents = "auto";
    prevBtn.style.pointerEvents = "auto";
  }, 2000);
};

carousel.addEventListener("click", (e) => {
  const btn = e.target.closest(".seeMore");
  if (!btn) return;

  e.preventDefault();
  e.stopPropagation();
  carousel.classList.add("showDetail");
});

backBtn.onclick = function () {
  carousel.classList.remove("showDetail");
};


