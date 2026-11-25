export type ClientOption = { id: number; name: string; kind: 'tag' | 'status' };
export type Client = { id: number; name: string; company: string | null; email: string | null; phone?: string | null; city?: string | null; options: ClientOption[] };
export type ClientHistory = {
    id: number;
    type: 'note' | 'status_change' | 'created' | string;
    body: string;
    meta?: Record<string, unknown> | null;
    created_at: string;
    user: { id: number | null; name: string };
};

export const demoOptions: ClientOption[] = [
    {id:1,kind:'status',name:'Activo'}, {id:2,kind:'status',name:'En seguimiento'}, {id:3,kind:'status',name:'Prospecto'},
    {id:4,kind:'tag',name:'Prioritario'}, {id:5,kind:'tag',name:'Referido'}, {id:6,kind:'tag',name:'Corporativo'},
];
export function demoClients(): Client[] {
    const names = ['Lucía Herrera','Diego Méndez','Sofía Navarro','Martín Vega','Camila Torres','Andrés Rojas','Valeria Castro','Mateo Reyes','Elena Soto','Pablo Fuentes','Isabel Romero','David Ruiz','Julia Vidal','Daniel Ortiz','Laura Gil','Gabriel León','Alba Ramos','Felipe Mora'];
    const companies = ['Studio Norte','Grupo Altura','Lumina Tech','Verde Capital','Nova Digital','Forma Labs'];
    return names.map((name,i)=>({id:i+1,name,company:companies[i%6],email:name.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(' ','.')+'@'+companies[i%6].toLowerCase().replace(' ','')+'.example',options:[demoOptions[i%3],demoOptions[3+Math.floor(i/2)%3]]}));
}
export function loadDemo<T>(key:string, fallback:T):T {
    try {return JSON.parse(localStorage.getItem(key)??'null')??fallback;} catch {return fallback;}
}
