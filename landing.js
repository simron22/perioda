/* =========================================
   PERIODA LANDING PAGE JS
   ========================================= */

document.addEventListener("DOMContentLoaded", function () {


    /* =========================================
       FAQ ACCORDION
    ========================================= */

    const faqQuestions =
        document.querySelectorAll(".faq-question");


    faqQuestions.forEach(function (question) {

        question.addEventListener("click", function () {

            const currentItem =
                question.parentElement;


            const allItems =
                document.querySelectorAll(".faq-item");


            allItems.forEach(function (item) {

                if (item !== currentItem) {

                    item.classList.remove("active");


                    const answer =
                        item.querySelector(".faq-answer");


                    if (answer) {

                        answer.style.maxHeight = null;

                    }

                }

            });


            currentItem.classList.toggle("active");


            const answer =
                currentItem.querySelector(".faq-answer");


            if (
                answer &&
                currentItem.classList.contains("active")
            ) {

                answer.style.maxHeight =
                    answer.scrollHeight + "px";

            } else if (answer) {

                answer.style.maxHeight = null;

            }

        });

    });



    /* =========================================
       SMOOTH SCROLL
    ========================================= */

    const links =
        document.querySelectorAll('a[href^="#"]');


    links.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                link.getAttribute("href");


            if (targetId === "#") {

                return;

            }


            const target =
                document.querySelector(targetId);


            if (target) {

                event.preventDefault();


                target.scrollIntoView({

                    behavior: "smooth",

                    block: "start"

                });

            }

        });

    });



    /* =========================================
       NAVBAR SCROLL EFFECT
    ========================================= */

    const navbar =
        document.querySelector(".navbar");


    if (navbar) {

        window.addEventListener("scroll", function () {

            if (window.scrollY > 30) {

                navbar.style.boxShadow =
                    "0 5px 25px rgba(120, 20, 60, 0.08)";

            } else {

                navbar.style.boxShadow =
                    "none";

            }

        });

    }



    /* =========================================
       BUTTON HOVER EFFECT
    ========================================= */

    const buttons =
        document.querySelectorAll(".primary-btn");


    buttons.forEach(function (button) {

        button.addEventListener("mouseenter", function () {

            button.style.transform =
                "translateY(-2px)";

        });


        button.addEventListener("mouseleave", function () {

            button.style.transform =
                "translateY(0)";

        });

    });

});