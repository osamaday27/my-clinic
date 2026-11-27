<div>
@if ($open)
    <div class="scrim" wire:click="close"></div>
    <div class="modal" role="dialog" aria-modal="true" @keydown.escape.window="$wire.close()">
        <div class="modal__box">
            <div class="card__h"><h2>{{ $patientId ? __('clinic.ui.edit_patient') : __('clinic.ui.new_patient') }}</h2>
                <button class="iconbtn" wire:click="close" aria-label="{{ __('clinic.ui.close') }}"><span class="ms">close</span></button></div>
            <div class="form">
                <div class="field"><label for="pf-ar">{{ __('clinic.ui.name_ar') }} *</label><input id="pf-ar" class="input" style="width:100%" maxlength="120" wire:model="name_ar" autofocus>
                    @error('name_ar')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-en">{{ __('clinic.ui.name_en') }}</label><input id="pf-en" class="input" style="width:100%" maxlength="120" dir="ltr" wire:model="name_en">
                    @error('name_en')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-ph">{{ __('clinic.ui.phone') }} *</label><input id="pf-ph" class="input num" style="width:100%" inputmode="tel" dir="ltr" wire:model.live.debounce.400ms="phone">
                    @if ($this->duplicatePhone)<div class="hint" style="color:var(--warn);font-weight:600">{{ __('clinic.ui.dup_phone') }}</div>@endif
                    @error('phone')<div class="err" role="alert">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-nid">{{ __('clinic.ui.national_id') }}</label><input id="pf-nid" class="input num" style="width:100%" inputmode="numeric" dir="ltr" wire:model="national_id">
                    @error('national_id')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-bd">{{ __('clinic.ui.birth_date') }}</label><input id="pf-bd" type="date" class="input" style="width:100%" max="{{ today()->toDateString() }}" wire:model="birth_date">
                    @error('birth_date')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-g">{{ __('clinic.ui.gender') }}</label><select id="pf-g" class="select" style="width:100%" wire:model="gender"><option value="">—</option><option value="male">{{ __('clinic.ui.male') }}</option><option value="female">{{ __('clinic.ui.female') }}</option></select></div>
                <div class="field full"><label for="pf-al">{{ __('clinic.ui.allergy') }}</label><input id="pf-al" class="input" style="width:100%" maxlength="1000" wire:model="allergies" placeholder="Penicillin, Sulfa">
                    <div class="hint">{{ __('clinic.ui.list_hint') }}</div>@error('allergies')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field full"><label for="pf-ch">{{ __('clinic.ui.chronic') }}</label><input id="pf-ch" class="input" style="width:100%" maxlength="1000" wire:model="chronic_conditions">
                    <div class="hint">{{ __('clinic.ui.list_hint') }}</div></div>
                <div class="field"><label for="pf-ic">{{ __('clinic.ui.insurance') }}</label>
                    <select id="pf-ic" class="select" style="width:100%" wire:model="insurance_company_id"><option value="">{{ __('clinic.ui.no_insurance') }}</option>
                        @foreach ($this->companies as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->coverage_percent }}%)</option>@endforeach</select>
                    @error('insurance_company_id')<div class="err">{{ $message }}</div>@enderror</div>
                <div class="field"><label for="pf-in">{{ __('clinic.ui.ins_number') }}</label><input id="pf-in" class="input num" style="width:100%" maxlength="60" dir="ltr" wire:model="insurance_number"></div>
            </div>
            <div class="row" style="padding:0 20px 20px;justify-content:flex-end">
                <button class="btn" wire:click="close">{{ __('clinic.ui.close') }}</button>
                <button class="btn btn--primary" wire:click="save" wire:loading.attr="disabled">{{ __('clinic.ui.save') }}</button>
            </div>
        </div>
    </div>
@endif
</div>

{{--
SYSTEM DESIGN:
Registration form with required markers, helper text under fields, inline errors, soft duplicate-phone warning and a coverage preview in the insurer list.

DATA STRUCTURE:

--}}
