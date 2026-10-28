
const categories = document.querySelectorAll(".category");

const faqData = {
    test1: {
        title: "Test 1",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test2: {
        title: "Test 2",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test3: {
        title: "Test 3",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test4: {
        title: "Test 4",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test5: {
        title: "Test 5",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test6: {
        title: "Test 6",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    },
    test7: {
        title: "Test 7",
        questions: [
            { question: "Question 1?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 2?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." },
            { question: "Question 3?", answer: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id." }
        ]
    }
};

function showQuestions(categoryButton) {
    const categoryGroup = categoryButton.closest(".category-group");
    const questionsPanel = categoryGroup.querySelector(".questions-panel");
    const categoryTitle = questionsPanel.querySelector(".category-title");
    const questionsList = questionsPanel.querySelector(".questions-list");

    const data = faqData[categoryButton.dataset.category];

    // Close all other categories
    document.querySelectorAll(".category-group").forEach((group) => {
        group.classList.remove("active");
        group.querySelector(".category").classList.remove("active");
        group.querySelector(".questions-panel").classList.remove("visible");
    });

    // Show the selected category
    categoryGroup.classList.add("active");
    categoryButton.classList.add("active");
    questionsPanel.classList.add("visible");

    categoryTitle.textContent = data.title;
    questionsList.innerHTML = "";

    // Create the questions
    data.questions.forEach((item) => {
        const faqItem = document.createElement("div");
        faqItem.classList.add("faq-item");

        const questionButton = document.createElement("button");
        questionButton.classList.add("question");
        questionButton.setAttribute("aria-expanded", "false");

        const questionText = document.createElement("span");
        questionText.textContent = item.question;

        const icon = document.createElement("span");
        icon.classList.add("icon");
        icon.textContent = "+";

        questionButton.append(questionText, icon);

        const answer = document.createElement("div");
        answer.classList.add("answer");

        const answerContent = document.createElement("div");
        answerContent.classList.add("answer-content");
        answerContent.textContent = item.answer;

        answer.appendChild(answerContent);

        questionButton.addEventListener("click", () => {
            const isOpen = faqItem.classList.toggle("open");

            questionButton.setAttribute("aria-expanded", String(isOpen));
            icon.textContent = isOpen ? "-" : "+";
        });

        faqItem.append(questionButton, answer);
        questionsList.appendChild(faqItem);
    });
}

// Make categories clickable
categories.forEach((category) => {
    category.addEventListener("click", () => {
        showQuestions(category);
    });
});

// Show Test 1 when the page loads
showQuestions(categories[0]);
