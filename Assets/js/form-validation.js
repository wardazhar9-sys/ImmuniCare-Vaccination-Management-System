(() => {
    const fields = "input:not([type='hidden']), select, textarea";

    const clearValidation = (field) => {
        if (field.dataset.validationMessage) {
            field.setCustomValidity("");
            delete field.dataset.validationMessage;
        }
    };

    const validateForm = (form) => {
        let firstInvalid = null;

        form.querySelectorAll(fields).forEach((field) => {
            clearValidation(field);

            if (
                field.required &&
                ["text", "email", "tel", "search", "password"].includes(field.type) &&
                field.value.trim() === ""
            ) {
                field.setCustomValidity("This field is required.");
                field.dataset.validationMessage = "true";
            }

            if (
                field.required &&
                field.tagName === "TEXTAREA" &&
                field.value.trim() === ""
            ) {
                field.setCustomValidity("This field is required.");
                field.dataset.validationMessage = "true";
            }

            if (field.validationMessage && !firstInvalid) {
                firstInvalid = field;
            }
        });

        form.querySelectorAll("input[name='confirm_password']").forEach((confirm) => {
            const password = form.querySelector(
                "input[name='new_password'], input[name='password']"
            );

            if (password && confirm.value !== password.value) {
                confirm.setCustomValidity("Passwords do not match.");
                confirm.dataset.validationMessage = "true";
                firstInvalid = firstInvalid || confirm;
            }
        });

        if (firstInvalid) {
            firstInvalid.reportValidity();
            return false;
        }

        return true;
    };

    document.querySelectorAll("form").forEach((form) => {
        const role = form.querySelector("select[name='role']");
        const hospitalFields = form.querySelectorAll(
            ".hospital-only input[name='phone'], .hospital-only input[name='city'], " +
            ".hospital-only textarea[name='address']"
        );

        const updateConditionalFields = () => {
            if (!role || hospitalFields.length === 0) return;
            const required = role.value === "hospital";
            hospitalFields.forEach((field) => {
                field.required = required;
            });
        };

        updateConditionalFields();
        role?.addEventListener("change", updateConditionalFields);

        form.addEventListener("submit", (event) => {
            updateConditionalFields();
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });

        form.querySelectorAll(fields).forEach((field) => {
            field.addEventListener("input", () => {
                clearValidation(field);
            });
            field.addEventListener("change", () => {
                clearValidation(field);
            });
        });
    });
})();
