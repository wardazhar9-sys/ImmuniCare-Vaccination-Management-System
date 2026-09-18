document.addEventListener("DOMContentLoaded", function () {

    // Mobile menu
    const menuToggle = document.getElementById("menuToggle");
    const navArea = document.getElementById("navArea");

    if (menuToggle && navArea) {
        menuToggle.addEventListener("click", function () {
            navArea.classList.toggle("open");
        });

        document.querySelectorAll(".nav-links a").forEach(function (link) {
            link.addEventListener("click", function () {
                navArea.classList.remove("open");
            });
        });
    }

    // Scroll reveal animation
    const revealItems = document.querySelectorAll(".reveal");

    const observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("show");
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.12
        }
    );

    revealItems.forEach(function (item) {
        observer.observe(item);
    });

    // Hero dots animation
    const dots = document.querySelectorAll(".hero-dots .dot");
    let currentDot = 0;

    function activateDot(index) {
        dots.forEach(function (dot) {
            dot.classList.remove("active");
        });

        if (dots[index]) {
            dots[index].classList.add("active");
        }
    }

    dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
            currentDot = index;
            activateDot(currentDot);
        });
    });

    // The reference currently shows one hero design.
    // The dots are kept interactive and gently cycle to give the page
    // the same polished carousel feel without changing the visible design.
    setInterval(function () {
        currentDot = (currentDot + 1) % dots.length;
        activateDot(currentDot);
    }, 4500);

    // Hero arrow buttons
    const leftArrow = document.querySelector(".hero-arrow-left");
    const rightArrow = document.querySelector(".hero-arrow-right");

    if (leftArrow) {
        leftArrow.addEventListener("click", function () {
            currentDot = (currentDot - 1 + dots.length) % dots.length;
            activateDot(currentDot);
        });
    }

    if (rightArrow) {
        rightArrow.addEventListener("click", function () {
            currentDot = (currentDot + 1) % dots.length;
            activateDot(currentDot);
        });
    }

});
