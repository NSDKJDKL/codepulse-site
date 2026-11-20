function revealCardsOnScroll() {
    if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const cards = document.querySelectorAll('.feature-card, .tech-card');
    const observer = new IntersectionObserver((entries) => {
        for (const { target, isIntersecting } of entries) {
            if (!isIntersecting) continue;
            target.classList.remove('card-waiting');
            observer.unobserve(target);
        }
    });

    for (const card of cards) {
        card.classList.add('card-waiting');
        observer.observe(card);
    }
}

revealCardsOnScroll();
