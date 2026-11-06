<?php
namespace App\Services;
use Illuminate\Support\Facades\{Cache,Http};
use Throwable;
class ApplicationUpdates
{
    public function version(): string { return trim(file_get_contents(base_path('VERSION'))); }
    private function key(): string { return 'application-release-check:'.hash('sha256',config('platform.updates.repository')); }
    public function cached(): ?array { return Cache::get($this->key()); }
    public function check(bool $force=false): array
    {
        $cached=$this->cached();
        // Bound manual checks too, to avoid exhausting GitHub's rate limit.
        if ($cached && (!$force || now()->diffInSeconds(\Carbon\Carbon::parse($cached['checked_at']),true)<60)) return $cached;
        $result=['status'=>'not_configured','latest'=>null,'url'=>null,'checked_at'=>now()->toIso8601String()];
        $token=config('platform.updates.token');
        if (is_string($token) && $token!=='') {
            try {
                $response=Http::acceptJson()->withToken($token)->withHeaders(['X-GitHub-Api-Version'=>'2022-11-28','User-Agent'=>'Blackmail-Update-Check'])
                    ->connectTimeout(3)->timeout(8)->withoutRedirecting()->get('https://api.github.com/repos/OmanGhost/Blackmail/releases/latest');
                if ($response->successful()) {
                    $data=$response->json();$tag=$data['tag_name']??'';
                    if (is_string($tag) && preg_match('/^v?(\d+\.\d+\.\d+)$/D',$tag,$match) && !($data['draft']??true) && !($data['prerelease']??true)) {
                        $result['status']='ok';$result['latest']=$match[1];
                        $result['url']='https://github.com/OmanGhost/Blackmail/releases/tag/'.rawurlencode($tag);
                    } else $result['status']='invalid_response';
                } else $result['status']=match($response->status()) {401,404=>'access_denied',403,429=>'rate_limited',default=>'unavailable'};
            } catch (Throwable) { $result['status']='unavailable'; }
        }
        // A pending update stays pending, so cache it longer. When up to date, recheck hourly so new releases show up quickly.
        $available=$result['status']==='ok' && version_compare($result['latest'],$this->version(),'>');
        Cache::put($this->key(),$result,now()->addHours($available?(int)config('platform.updates.interval_hours',12):1));
        return $result;
    }
    public function displayStatus(?array $result): string
    {
        if (!$result) return 'not_checked';
        if ($result['status']!=='ok') return $result['status'];
        return version_compare($result['latest'],$this->version(),'>')?'available':(version_compare($result['latest'],$this->version(),'<')?'ahead':'current');
    }
}
