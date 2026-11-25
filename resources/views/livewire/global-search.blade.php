<div class="search" x-data @keydown.window="if ($event.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.q.focus() }" @click.outside="$wire.set('query','')">
    <span class="ms">search</span>
    <input x-ref="q" type="search" class="" wire:model.live.debounce.300ms="query" placeholder="{{ __('clinic.ui.search') }}" aria-label="{{ __('clinic.ui.search') }}" autocomplete="off">
    @if (mb_strlen(trim($query)) >= 2)
        <div class="search__pop" role="listbox">
            @forelse ($this->results as $p)
                <button type="button" role="option" wire:click="open({{ $p->id }})">
                    <span><strong>{{ $p->name }}</strong><br><span class="sub num">{{ $p->file_number }}</span></span>
                    <span class="sub num">{{ $p->phone }}</span>
                </button>
            @empty
                <div class="empty">{{ __('clinic.ui.empty') }}</div>
            @endforelse
        </div>
    @endif
</div>

{{--
SYSTEM DESIGN:
Instant search dropdown; press '/' to focus. Results capped at 6.

DATA STRUCTURE:
Debounced input -> Top-6 query.
--}}
