@props(['route', 'label'])

<a href="{{ route($route) }}"
   @class([
       'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
       'bg-hotel-500/15 text-hotel-200' => request()->routeIs($route),
       'text-slate-400 hover:bg-white/5 hover:text-white' => ! request()->routeIs($route),
   ])>
    <span @class(['h-2 w-2 rounded-full', 'bg-hotel-400' => request()->routeIs($route), 'bg-slate-700 group-hover:bg-slate-500' => ! request()->routeIs($route)])></span>
    <span>{{ $label }}</span>
</a>
