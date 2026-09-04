<div
    x-data="{
        show: false,
        message: '',
        type: 'success',
        notify(msg, t = 'success') {
            this.message = msg;
            this.type = t;
            this.show = true;
            setTimeout(() => this.show = false, 3000);
        }
    }"
    @toast.window="notify($event.detail.message, $event.detail.type ?? 'success')"
    class="pointer-events-none fixed bottom-8 end-8 z-[200]"
>
    <div
        x-show="show"
        x-transition
        x-cloak
        :class="type === 'error' ? 'bg-brand-red text-white' : 'bg-brand-green text-brand-bg'"
        class="pointer-events-auto rounded-xl px-5 py-3.5 text-sm font-bold shadow-lg"
        x-text="message"
    ></div>
</div>
