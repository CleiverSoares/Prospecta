import Alpine from 'alpinejs';
import { registrarMapaUnidade } from './admin/mapa-unidade';
import { registrarMapaPainel } from './admin/mapa-painel';
import { registrarFluxoCampo } from './app/fluxo-campo';

window.Alpine = Alpine;

registrarMapaUnidade(Alpine);
registrarMapaPainel(Alpine);
registrarFluxoCampo(Alpine);

Alpine.start();
