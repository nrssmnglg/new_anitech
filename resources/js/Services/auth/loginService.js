export function submitOfficeLogin(form, loginUrl) {
    return form.post(loginUrl, {
        preserveScroll: true,
    });
}
