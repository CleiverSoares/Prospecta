import Alpine from 'alpinejs';
import { registrarMapaUnidade } from './admin/mapa-unidade';

window.Alpine = Alpine;

registrarMapaUnidade(Alpine);

Alpine.start();
