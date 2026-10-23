import assert from 'node:assert/strict';
import { initCarousel } from '../public/assets/js/modules/carousel.js';

class Element {
    constructor() { this.listeners = {}; this.attributes = {}; this.hidden = false; this.textContent = ''; }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    emit(name, event = {}) { this.listeners[name]?.(event); }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
    querySelector() { return { textContent: 'Titre de la photo' }; }
}
const slides = Array.from({ length: 4 }, () => new Element());
const dots = slides.map((_, i) => Object.assign(new Element(), { dataset: { goTo: String(i) } }));
const elements = Object.fromEntries(['[data-carousel-controls]', '[data-counter]', '[data-carousel-status]', '[data-previous]', '[data-next]', '.stories-slides'].map(key => [key, new Element()]));
const root = new Element();
let ready = false;
root.contains = () => false;
root.classList = { contains: () => ready, add: () => { ready = true; } };
root.querySelector = name => elements[name];
root.querySelectorAll = name => name === '[data-slide]' ? slides : dots;
const media = new Element();
media.matches = false;
let callback;
let schedules = 0;
globalThis.setTimeout = (fn, delay) => { assert.equal(delay, 3000); callback = fn; schedules++; return 1; };
globalThis.clearTimeout = () => { callback = null; };
globalThis.window = { matchMedia: () => media };
globalThis.document = new Element();
document.hidden = false;
document.querySelectorAll = () => [root];
initCarousel();
const active = () => slides.findIndex(slide => !slide.hidden);
assert.equal(active(), 0);
assert.equal(elements['[data-carousel-controls]'].hidden, false);
callback();
assert.equal(active(), 1, 'Automatic progression');
elements['[data-next]'].emit('click');
assert.equal(active(), 2);
assert.ok(callback, 'Pointer navigation keeps automatic progression available');
assert.equal(dots[2].attributes['aria-current'], 'true');
assert.match(elements['[data-carousel-status]'].textContent, /Photo 3 sur 4/);
root.emit('keydown', { key: 'ArrowLeft', preventDefault() {} });
assert.equal(active(), 1);
root.emit('focusout', { relatedTarget: null });
dots[3].emit('click');
elements['[data-next]'].emit('click');
assert.equal(active(), 0, 'Loop back');
assert.ok(callback);
root.emit('mouseenter');
assert.equal(callback, null, 'Pause on hover');
root.emit('mouseleave');
assert.ok(callback);
root.emit('focusin', { target: elements['[data-next]'] });
assert.equal(callback, null, 'Pause on focus');
media.matches = true;
media.emit('change');
assert.equal(callback, null, 'Reduced-motion preference stops rotation');
const previousSchedules = schedules;
initCarousel();
assert.equal(schedules, previousSchedules, 'Do not initialize twice');
console.log('Carrousel : lecture, pause, clavier, pagination, boucle, focus et mouvements réduits vérifiés.');
