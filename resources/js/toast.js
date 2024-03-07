import Toastify from "toastify-js";


export function showToast(text, type, duration = false) {

    let avatar = undefined;
    if(type == "success"){
        avatar = "/data/images/icons/check_icon.svg";
    } else if(type == "error"){
        avatar = "/data/images/icons/warning_icon.svg";
    }

    Toastify({
        text: text ?? defaultErrorMessageFetch,
        className: type,
        duration: duration ?? undefined,
        avatar: avatar
    }).showToast();

}
