export type Opportunity = { id:number; name:string; company:string; client_id?:number|null; client?:{id:number;name:string}|null; amount:number|string; stage:string; created_at:string };
export const stages = ['Prospección','Calificación','Propuesta','Negociación','Ganada'];
export const colors = ['#ff7847','#ffbf65','#9e8ce2','#65b9ac','#6593de'];
export const money = (n:number) => '$' + new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(n);
const accounts = ['Studio Norte','Grupo Altura','Lumina Tech','Verde Capital','Nova Digital','Forma Labs','Atlas Group','Brava Studio'];
const names = ['Expansión de servicios','Consultoría estratégica','Implementación CRM','Renovación anual','Desarrollo de plataforma','Integración digital'];
export function sampleRecords(): Opportunity[] {
    const today = new Date();
    return Array.from({length:96},(_,i)=>{
        const date = new Date(today.getFullYear(),today.getMonth()-Math.floor(i/16),Math.max(1,(i<16?today.getDate():28)-i%16));
        return {id:1000-i,name:names[i%6],company:accounts[i%8],stage:stages[[3,2,0,1,4,0,4,2,1,4,0,4,0,4,1,4][i%16]],amount:[12500,8750,6200,15000,9800,4600,7200,6500,7800,5850,7200,8700,3800,6400,4700,10500][i%16]*[1,.82,.9,.62,.68,.49][Math.floor(i/16)],created_at:date.toISOString()};
    });
}
