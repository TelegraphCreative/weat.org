function closeModal () {
	var body = document.querySelector("body");
	var btn = document.querySelector("#closeModal")
	
	
	body.classList.toggle("modal__shown");
		
	function transformStart() {
        body.classList.toggle("modal__shown");
        body.classList.toggle("modal__hidden");
    }
	
    btn.addEventListener("click", transformStart);
	
}

export {closeModal};