export type MunicipalMessage = { id:string; channel:string; office:string; date:string; subject:string; body:string; reference:string; priority:'Normal'|'High' };
const channels=['General','Department Heads','MPDO Planning','Project Monitoring','Mayor’s Office','Inter-Office Coordination'];
const topics=[
['Municipal Planning and Development Office','Annual investment program inputs','Please review the current office submission and confirm any remaining documentary requirements.','Annual Investment Program'],
['Municipal Engineering Office','Project accomplishment validation','Field accomplishment figures have been updated for the next project monitoring review.','Project monitoring register'],
['Municipal Budget Office','Budget utilization reporting','Current office utilization reports are due for consolidation before the next finance coordination meeting.','Budget utilization report'],
['Municipal Administrator’s Office','Pending routed correspondence','Please review pending routed records assigned to your office and update action status where applicable.','Correspondence routing register'],
['Municipal Health Office','Health program coordination','Program implementation notes and facility concerns are ready for the scheduled health review.','Local Health Investment Plan'],
['Municipal Disaster Risk Reduction and Management Office','Preparedness coordination','Response equipment readiness and barangay coordination items are listed for review.','Preparedness checklist'],
['Municipal Environment and Natural Resources Office','Waste diversion reporting','Barangay waste diversion submissions have been consolidated for the next board review.','Ecological Solid Waste Management Plan'],
['Municipal Agriculture Office','Field program update','Current agriculture and fisheries field activities have been consolidated for planning reference.','Agriculture and Fisheries Development Plan'],
['Municipal Social Welfare and Development Office','Social protection coordination','Pending sector assistance and referral coordination items are listed for inter-office follow-up.','Social protection program records'],
['Municipal Tourism Office','Tourism calendar coordination','Please check the municipal tourism calendar for office dependencies and required preparations.','Tourism activity calendar'],
['Municipal Treasurer’s Office','Revenue status coordination','Current collection figures are available for the scheduled revenue and budget discussion.','Revenue performance report'],
['Sangguniang Bayan Secretariat','Legislative calendar notice','The current legislative calendar and referred records are available for office reference.','Legislative calendar']
] as const;
export const municipalMessages:MunicipalMessage[]=topics.flatMap((topic,i)=>[0,1,2].map((cycle)=>({id:`MSG-26-${String(i*3+cycle+1).padStart(3,'0')}`,channel:channels[(i+cycle)%channels.length],office:topic[0],date:`2026-09-${String(2+((i*2+cycle)%13)).padStart(2,'0')} ${cycle===0?'09:10':cycle===1?'13:40':'16:05'}`,subject:topic[1],body:topic[2],reference:topic[3],priority:(i+cycle)%7===0?'High':'Normal'})));
export const messageChannels=channels;
