<?php
declare(strict_types=1);
namespace AymudoStarter;
use Aymudo\Client;
final class Dashboard
{
    private array $capabilities=[];private array $operations=[];
    public function __construct(private readonly Client $client){}
    public function discover():array
    {
        $this->capabilities=$this->client->capabilities();$catalog=$this->client->operations();$this->operations=(array)($catalog['operations']??[]);
        return ['capabilities'=>$this->capabilities,'operations'=>$this->operations,'modules'=>$this->modules()];
    }
    public function modules():array
    {
        $friendly=array_fill_keys((array)($this->capabilities['operations']??[]),true);$keys=[];foreach($this->operations as $operation){$key=(string)($operation['operationKey']??$operation['key']??'');if($key!=='')$keys[$key]=true;}
        $has=static function(array $prefixes)use($friendly,$keys):bool{foreach(array_keys($friendly+$keys)as $key)foreach($prefixes as $prefix)if($key===$prefix||str_starts_with($key,$prefix.'.'))return true;return false;};
        $definitions=[
            'overview'=>['Overview','Workspace identity and enabled features',true],
            'appointments'=>['Appointments','Calendar, attendance and appointment lifecycle',$has(['appointments','appointment'])],
            'customers'=>['Customers','Profiles and customer history',$has(['customers','customer'])],
            'conversations'=>['Conversations','WhatsApp, Instagram, Facebook and supported inboxes',$has(['conversations','social.inbox','support'])],
            'social'=>['Social content','Posts, stories, videos, comments and replies',$has(['socialPosts','social.content','social.meta'])],
            'tasks'=>['Tasks','Operational tasks and reminders',$has(['tasks','operation.tasks'])],
            'additions'=>['Additions','Open additions and accounting activity',$has(['additions','addition','restaurant.addition'])],
            'notifications'=>['Notifications','Workspace notifications',$has(['notifications','operation.notifications'])],
            'analytics'=>['Analytics','Operational and conversion overview',$has(['analytics','customer.outcome-insights'])],
            'operations'=>['API operations','Exact assigned schemas and rules',true],
        ];
        $out=[];foreach($definitions as $id=>[$title,$description,$enabled])if($enabled)$out[$id]=compact('title','description');return $out;
    }
    public function data(string $view):array
    {
        return match($view){
            'overview'=>$this->capabilities,
            'appointments'=>$this->client->appointments(['from'=>date('Y-m-d'),'to'=>date('Y-m-d',strtotime('+30 days'))]),
            'customers'=>$this->client->customers(['page'=>1,'limit'=>50]),
            'conversations'=>$this->client->conversations(),
            'social'=>$this->client->socialPosts(),
            'tasks'=>$this->client->tasks(['includeCompleted'=>true,'limit'=>100]),
            'additions'=>$this->client->additions(['from'=>date('Y-m-d',strtotime('-7 days')),'to'=>date('Y-m-d')]),
            'notifications'=>$this->client->notifications(),
            'analytics'=>$this->client->analytics(['from'=>date('Y-m-d',strtotime('-30 days')),'to'=>date('Y-m-d')]),
            'operations'=>['operations'=>$this->operations],
            default=>throw new \InvalidArgumentException('Unknown dashboard view.'),
        };
    }

    public function operation(string $operationKey):?array
    {
        foreach($this->operations as $operation){
            if((string)($operation['operationKey']??$operation['key']??'')===$operationKey)return $operation;
        }
        return null;
    }

    public function execute(string $operationKey,array $input,?string $idempotencyKey=null):array
    {
        if($this->operation($operationKey)===null)throw new \InvalidArgumentException('The requested operation is not assigned to this workspace.');
        return $this->client->execute($operationKey,$input,$idempotencyKey);
    }
}
