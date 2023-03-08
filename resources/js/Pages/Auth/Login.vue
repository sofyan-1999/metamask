<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Web3 from 'web3/dist/web3.min.js';
import { Mailchain } from '@mailchain/sdk';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const loginWeb3 = async() => {
    if (!window.ethereum) {
        alert('MetaMask not detected. Please try again from a MetaMask enabled browser.')
    }

    const web3 = new Web3(window.ethereum);

    const message = (await axios.get('/ethereum/signature')).data;
    const address = (await web3.eth.requestAccounts())[0];
    const signature = await web3.eth.personal.sign(message, address);

    try {
        await axios.post('/ethereum/authenticate', {
            'address': address,
            'signature': signature,
        });

        window.location.href = '/dashboard';
    } catch(e) {
        alert(e.message);
    }
};

const sendMail = async()=> {
    const secretRecoveryPhrase = import.meta.env.VITE_SECRET_RECOVERY_PHRASE; // 25 word mnemonicPhrase
    const mailchain = Mailchain.fromSecretRecoveryPhrase(secretRecoveryPhrase);

    const result = await mailchain.sendMail({
        from: `sofyan@mailchain.com`, // sender address
        to: [`0xd3c2e54024d33f819615082ec92375C179adC9D8@ethereum.mailchain.com`],// list of recipients (blockchain or mailchain addresses)
        subject: 'My first message', // subject line
        content: {
            text: 'Hello Mailchain 👋', // plain text body
            html: '<p>Hello Mailchain 👋</p>', // html body
        },
    });

    console.log(result);
};

</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>
        <div class="text-center pt-4">
            <PrimaryButton class="ml-4" @click="sendMail">
                Send mail
            </PrimaryButton>
        </div>
        <div class="text-center pt-4 pb-8 border-b border-gray-200">
            <PrimaryButton class="ml-4" @click="loginWeb3">
                Login with MetaMask
            </PrimaryButton>
        </div>

        <div class="py-6 text-sm text-gray-500 text-center">
            or login with your credentials…
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Forgot your password?
                </Link>

                <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
